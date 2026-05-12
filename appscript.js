/**
 * CRIMSON SCAN - SISTEMA UNIFICADO DE GESTIÓN
 * Versión 2.2 (Gestión CRUD Completa + Métricas + Discord)
 */

// --- CONFIGURACIÓN ---
const CARPETA_RAIZ_ID = '1MEkmLbc2xbvZ6KxL-Dqlhw4JgqAy5Lzp';
const HOJA_CALCULO_ID = '15rsdxNP8gcyhwTyNWfI8oRSAb8ELehkxO5wu9kCIJZw';
const DISCORD_WEBHOOK = 'https://discordapp.com/api/webhooks/1481805598373707929/5RGhzp__my2SrASyDjKFY28WIL3T-uqaVOqqJbimX_hlalibEHZiNEWf4NGxWt3tlXW3';

/**
 * Maneja las peticiones GET (Creación, Edición, Estado, Eliminación)
 */
function doGet(e) {
  var action = e.parameter.action;
  var ss = SpreadsheetApp.openById(HOJA_CALCULO_ID);
  var sheet = ss.getSheets()[0];

  // LISTAR PROYECTOS desde Drive
  if (action === 'listarProyectos') {
    var folder = DriveApp.getFolderById(CARPETA_RAIZ_ID);
    var folders = folder.getFolders();
    var nombres = [];
    while (folders.hasNext()) {
      nombres.push(folders.next().getName());
    }
    nombres.sort();
    return jsonResponse({exito: true, datos: nombres});
  }

  // HISTORIAL desde Google Sheets
  if (action === 'historial') {
    var lastRow = sheet.getLastRow();
    if (lastRow <= 1) return jsonResponse({exito: true, datos: []});
    var values = sheet.getRange(2, 1, lastRow - 1, Math.max(sheet.getLastColumn(), 6)).getValues();
    var filas = values.filter(function(f) { return f[0] && f[0].toString().trim() !== ''; });
    filas.reverse();
    return jsonResponse({exito: true, datos: filas});
  }

  // EDITAR REGISTRO
  if (action === "editarRegistro") {
    var fila = parseInt(e.parameter.fila);
    sheet.getRange(fila, 2).setValue(e.parameter.manga);
    sheet.getRange(fila, 3).setValue(e.parameter.cap);
    sheet.getRange(fila, 4).setValue(e.parameter.etapa);
    return jsonResponse({exito: true, mensaje: "Registro actualizado"});
  }

  // 2. CAMBIAR ESTADO (Activo/Inactivo)
  if (action === "cambiarEstado") {
    var fila = parseInt(e.parameter.fila);
    var estado = e.parameter.estado;
    sheet.getRange(fila, 6).setValue(estado); // Columna F
    return jsonResponse({exito: true, mensaje: "Estado cambiado a " + estado});
  }

  // 3. ELIMINAR REGISTRO (Físico)
  if (action === "eliminarRegistro") {
    var fila = parseInt(e.parameter.fila);
    sheet.deleteRow(fila);
    return jsonResponse({exito: true, mensaje: "Registro eliminado"});
  }

  // 4. CREAR PROYECTO
  if (action === 'crearProyecto') {
    return crearProyecto(e.parameter.nombre);
  }

  return ContentService.createTextOutput("Crimson API Online - " + new Date().toISOString());
}

/**
 * Maneja las peticiones POST (Subida de archivos)
 */
function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var action = data.action || 'initUpload';

    if (action === 'initUpload') {
      return initUpload(data);
    } else if (action === 'registrarSubida') {
      return registrarSubida(data);
    }
  } catch(err) {
    return jsonResponse({ exito: false, mensaje: "Error en Apps Script: " + err.toString() });
  }
}

function initUpload(data) {
  var folder = DriveApp.getFolderById(CARPETA_RAIZ_ID);
  var pFolders = folder.getFoldersByName(data.proyecto);
  if(!pFolders.hasNext()) throw new Error("El proyecto '" + data.proyecto + "' no existe.");
  var pFolder = pFolders.next();
  
  var eFolders = pFolder.getFoldersByName(data.etapa);
  var eFolder = eFolders.hasNext() ? eFolders.next() : pFolder.createFolder(data.etapa);
  
  var destino = eFolder;
  if(data.etapa !== "01. RAWs") {
    var capNombre = "Capítulo " + data.capitulo;
    var cFolders = eFolder.getFoldersByName(capNombre);
    destino = cFolders.hasNext() ? cFolders.next() : eFolder.createFolder(capNombre);
  }

  var metadata = { name: data.filename, parents: [destino.getId()] };
  var options = {
    method: "post",
    contentType: "application/json",
    payload: JSON.stringify(metadata),
    headers: { 
      "Authorization": "Bearer " + ScriptApp.getOAuthToken(),
      "X-Upload-Content-Type": data.mimeType,
      "X-Upload-Content-Length": data.fileSize
    },
    followRedirects: false,
    muteHttpExceptions: true
  };
  
  var response = UrlFetchApp.fetch("https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable", options);
  var uploadUrl = response.getAllHeaders()["Location"] || response.getAllHeaders()["location"];
  
  return jsonResponse({ exito: true, uploadUrl: uploadUrl });
}

function registrarSubida(data) {
  try {
    var hoja = SpreadsheetApp.openById(HOJA_CALCULO_ID).getSheets()[0];
    var fecha = Utilities.formatDate(new Date(), "GMT-4", "dd/MM/yyyy HH:mm");
    // Añadimos columna de estado por defecto "Activo"
    hoja.appendRow([fecha, data.proyecto, data.capitulo, data.etapa, data.filename, "Activo"]);
  } catch(e) { console.error("Error Excel: " + e.toString()); }

  if (DISCORD_WEBHOOK) {
    try {
      var payload = {
        embeds: [{
          title: "🚀 Nuevo Archivo Subido",
          description: "Se ha procesado una nueva subida desde el Panel Web.",
          color: 15158332,
          fields: [
            { name: "Proyecto", value: data.proyecto, inline: true },
            { name: "Capítulo", value: data.capitulo.toString(), inline: true },
            { name: "Etapa", value: data.etapa, inline: true },
            { name: "Nombre", value: data.filename }
          ],
          footer: { text: "Crimson Scan" },
          timestamp: new Date().toISOString()
        }]
      };
      UrlFetchApp.fetch(DISCORD_WEBHOOK, {
        method: "post", contentType: "application/json", payload: JSON.stringify(payload)
      });
    } catch(e) { console.error("Error Discord: " + e.toString()); }
  }
  
  return jsonResponse({ exito: true });
}

function crearProyecto(nombre) {
  try {
    var folderRaiz = DriveApp.getFolderById(CARPETA_RAIZ_ID);
    if (folderRaiz.getFoldersByName(nombre).hasNext()) {
      return jsonResponse({ exito: false, mensaje: "El proyecto ya existe." });
    }
    
    var pFolder = folderRaiz.createFolder(nombre);
    var etapas = ["01. RAWs", "02. Traducción", "03. Limpieza y Redibujo", "04. Typos", "05. Control de Calidad"];
    etapas.forEach(function(etapa) {
      pFolder.createFolder(etapa);
    });
    
    return jsonResponse({ exito: true, mensaje: "Proyecto creado." });
  } catch(e) {
    return jsonResponse({ exito: false, mensaje: e.toString() });
  }
}

function jsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
