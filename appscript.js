/**
 * CRIMSON SCAN - SISTEMA UNIFICADO DE GESTIÓN
 * Versión 2.4 - Correcciones: hoja correcta, carpetas RAWs, carpetaId, usuario
 */

// --- CONFIGURACIÓN ---
const CARPETA_RAIZ_ID = '1MEkmLbc2xbvZ6KxL-Dqlhw4JgqAy5Lzp';
const HOJA_CALCULO_ID = '15rsdxNP8gcyhwTyNWfI8oRSAb8ELehkxO5wu9kCIJZw';
const HOJA_REGISTROS  = 'Respuestas de formulario 1';
const DISCORD_WEBHOOK = 'https://discordapp.com/api/webhooks/1481805598373707929/5RGhzp__my2SrASyDjKFY28WIL3T-uqaVOqqJbimX_hlalibEHZiNEWf4NGxWt3tlXW3';

/**
 * GET: listarProyectos | historial | editarRegistro | cambiarEstado | eliminarRegistro | crearProyecto
 */
function doGet(e) {
  var action = e.parameter.action;
  var ss    = SpreadsheetApp.openById(HOJA_CALCULO_ID);
  var sheet = ss.getSheetByName(HOJA_REGISTROS) || ss.getSheets()[0];

  if (action === 'listarProyectos') {
    var folder  = DriveApp.getFolderById(CARPETA_RAIZ_ID);
    var folders = folder.getFolders();
    var nombres = [];
    while (folders.hasNext()) nombres.push(folders.next().getName());
    nombres.sort();
    return jsonResponse({ exito: true, datos: nombres });
  }

  if (action === 'historial') {
    var lastRow = sheet.getLastRow();
    if (lastRow <= 1) return jsonResponse({ exito: true, datos: [] });
    var values = sheet.getRange(2, 1, lastRow - 1, Math.max(sheet.getLastColumn(), 7)).getValues();
    var filas  = values.filter(function(f) { return f[0] && f[0].toString().trim() !== ''; });
    filas.reverse();
    return jsonResponse({ exito: true, datos: filas });
  }

  if (action === 'editarRegistro') {
    var fila = parseInt(e.parameter.fila);
    if (fila < 2) return jsonResponse({ exito: false, mensaje: 'Fila inválida' });
    sheet.getRange(fila, 2).setValue(e.parameter.manga);
    sheet.getRange(fila, 3).setValue(e.parameter.cap);
    sheet.getRange(fila, 4).setValue(e.parameter.etapa);
    return jsonResponse({ exito: true, mensaje: 'Registro actualizado' });
  }

  if (action === 'cambiarEstado') {
    var fila = parseInt(e.parameter.fila);
    if (fila < 2) return jsonResponse({ exito: false, mensaje: 'Fila inválida' });
    sheet.getRange(fila, 6).setValue(e.parameter.estado);
    return jsonResponse({ exito: true, mensaje: 'Estado cambiado a ' + e.parameter.estado });
  }

  if (action === 'eliminarRegistro') {
    var fila = parseInt(e.parameter.fila);
    if (fila < 2) return jsonResponse({ exito: false, mensaje: 'Fila inválida' });
    sheet.deleteRow(fila);
    return jsonResponse({ exito: true, mensaje: 'Registro eliminado' });
  }

  if (action === 'crearProyecto') {
    return crearProyecto(e.parameter.nombre);
  }

  if (action === 'listarProyectosConId') {
    var folder  = DriveApp.getFolderById(CARPETA_RAIZ_ID);
    var folders = folder.getFolders();
    var datos   = [];
    while (folders.hasNext()) {
      var f = folders.next();
      datos.push({ nombre: f.getName(), id: f.getId() });
    }
    datos.sort(function(a, b) { return a.nombre.localeCompare(b.nombre); });
    return jsonResponse({ exito: true, datos: datos });
  }

  if (action === 'verificarCapitulo') {
    var proyDriveId = e.parameter.proyecto_drive_id;
    var capNum      = e.parameter.capitulo;
    if (!proyDriveId || !capNum) return jsonResponse({ exito: false, mensaje: 'Faltan parametros' });
    var capInt     = parseInt(capNum);
    var capNombre  = 'Capítulo ' + capNum;
    var capNombreB = 'Capitulo '  + capNum;
    var etapasMap  = {
      raw:   '01. RAWs',
      trad:  '02. Traducción',
      clean: '03. Limpieza y Redibujo',
      type:  '04. Typos',
      proof: '05. Control de Calidad'
    };
    var resultado = {};
    try {
      var pFolder = DriveApp.getFolderById(proyDriveId);
      Object.keys(etapasMap).forEach(function(clave) {
        try {
          var eFolders = pFolder.getFoldersByName(etapasMap[clave]);
          if (!eFolders.hasNext()) { resultado[clave] = false; return; }
          var eFolder = eFolders.next();
          // RAWs: buscar subcarpeta O archivo directo Cap_N.zip/rar
          if (clave === 'raw') {
            var c1 = eFolder.getFoldersByName(capNombre);
            var c2 = eFolder.getFoldersByName(capNombreB);
            if (c1.hasNext() || c2.hasNext()) { resultado[clave] = true; return; }
            var files = eFolder.getFiles();
            while (files.hasNext()) {
              var m = files.next().getName().toLowerCase().match(/^cap[_\-\s]?0*(\d+)/);
              if (m && parseInt(m[1]) === capInt) { resultado[clave] = true; return; }
            }
            resultado[clave] = false;
          } else {
            var c1 = eFolder.getFoldersByName(capNombre);
            var c2 = eFolder.getFoldersByName(capNombreB);
            resultado[clave] = c1.hasNext() || c2.hasNext();
          }
        } catch(err) { resultado[clave] = false; }
      });
      return jsonResponse({ exito: true, etapas: resultado });
    } catch(err) {
      return jsonResponse({ exito: false, mensaje: 'Error: ' + err.toString() });
    }
  }

  if (action === 'buscarCapituloConEnlaces') {
    var proyDriveId = e.parameter.proyecto_drive_id;
    var capNum      = e.parameter.capitulo;
    var etapaFiltro = e.parameter.etapa || 'Todas';
    if (!proyDriveId || !capNum) return jsonResponse({ exito: false, mensaje: 'Faltan parametros' });
    return buscarCapituloConEnlaces(proyDriveId, capNum, etapaFiltro);
  }

  return ContentService.createTextOutput('Crimson API Online - ' + new Date().toISOString());
}

/**
 * POST: initUpload | registrarSubida
 */
function doPost(e) {
  try {
    var data   = JSON.parse(e.postData.contents);
    var action = data.action || 'initUpload';

    if (action === 'initUpload')      return initUpload(data);
    if (action === 'registrarSubida') return registrarSubida(data);

    return jsonResponse({ exito: false, mensaje: 'Acción desconocida: ' + action });
  } catch(err) {
    return jsonResponse({ exito: false, mensaje: 'Error en Apps Script: ' + err.toString() });
  }
}

/**
 * Inicia una subida resumible a Drive.
 * Crea subcarpeta "Capítulo N" en TODAS las etapas (incluyendo RAWs)
 * para que la verificación Drive sea consistente.
 */
function initUpload(data) {
  var folder   = DriveApp.getFolderById(CARPETA_RAIZ_ID);
  var pFolders = folder.getFoldersByName(data.proyecto);
  if (!pFolders.hasNext()) {
    throw new Error("El proyecto '" + data.proyecto + "' no existe en Drive.");
  }
  var pFolder = pFolders.next();

  // Buscar o crear carpeta de etapa
  var eFolders = pFolder.getFoldersByName(data.etapa);
  var eFolder  = eFolders.hasNext() ? eFolders.next() : pFolder.createFolder(data.etapa);

  // Buscar o crear subcarpeta "Capítulo N" (en todas las etapas, incluido RAWs)
  var capNombre = 'Capítulo ' + data.capitulo;
  var cFolders  = eFolder.getFoldersByName(capNombre);
  var destino   = cFolders.hasNext() ? cFolders.next() : eFolder.createFolder(capNombre);

  var metadata = { name: data.filename, parents: [destino.getId()] };
  var options  = {
    method:          'post',
    contentType:     'application/json',
    payload:         JSON.stringify(metadata),
    headers: {
      'Authorization':        'Bearer ' + ScriptApp.getOAuthToken(),
      'X-Upload-Content-Type': data.mimeType,
      'X-Upload-Content-Length': data.fileSize
    },
    followRedirects:    false,
    muteHttpExceptions: true
  };

  var response  = UrlFetchApp.fetch('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable', options);
  var uploadUrl = response.getAllHeaders()['Location'] || response.getAllHeaders()['location'];

  if (!uploadUrl) {
    throw new Error('Drive no devolvió URL de subida. Código: ' + response.getResponseCode());
  }

  return jsonResponse({ exito: true, uploadUrl: uploadUrl });
}

/**
 * Notifica Discord al registrar una subida.
 * El guardado en base de datos lo hace upload_api.php directamente.
 */
function registrarSubida(data) {
  if (DISCORD_WEBHOOK) {
    try {
      var payload = {
        embeds: [{
          title:       '📤 Nueva Subida — Crimson Scan',
          description: 'Archivo procesado desde el Panel Web.',
          color:       15158332,
          fields: [
            { name: 'Proyecto',  value: data.proyecto          || '—', inline: true },
            { name: 'Capítulo',  value: String(data.capitulo   || '—'), inline: true },
            { name: 'Etapa',     value: data.etapa             || '—', inline: true },
            { name: 'Archivo',   value: data.filename          || '—', inline: false },
            { name: 'Subido por',value: data.usuario           || 'Desconocido', inline: true }
          ],
          footer:    { text: 'Crimson Scan' },
          timestamp: new Date().toISOString()
        }]
      };
      UrlFetchApp.fetch(DISCORD_WEBHOOK, {
        method: 'post', contentType: 'application/json', payload: JSON.stringify(payload)
      });
    } catch(e) {
      console.error('Error Discord: ' + e.toString());
    }
  }

  return jsonResponse({ exito: true });
}

/**
 * Crea carpeta de proyecto con las 5 subcarpetas de etapa.
 * Devuelve carpetaId para que el panel pueda guardarlo en la BD.
 */
function crearProyecto(nombre) {
  try {
    var folderRaiz = DriveApp.getFolderById(CARPETA_RAIZ_ID);

    if (folderRaiz.getFoldersByName(nombre).hasNext()) {
      // Proyecto ya existe — devolver el ID existente
      var existente = folderRaiz.getFoldersByName(nombre).next();
      return jsonResponse({ exito: false, mensaje: 'El proyecto ya existe.', carpetaId: existente.getId() });
    }

    var pFolder = folderRaiz.createFolder(nombre);
    ['01. RAWs', '02. Traducción', '03. Limpieza y Redibujo', '04. Typos', '05. Control de Calidad']
      .forEach(function(etapa) { pFolder.createFolder(etapa); });

    // BUG FIX: devolver carpetaId para que el panel lo guarde en la BD
    return jsonResponse({ exito: true, mensaje: 'Proyecto creado.', carpetaId: pFolder.getId() });
  } catch(e) {
    return jsonResponse({ exito: false, mensaje: e.toString() });
  }
}

/**
 * Busca archivos de un capítulo en Drive y devuelve IDs + nombres.
 * Maneja dos nomenclaturas para RAWs:
 *   - Subcarpeta:   RAWs/Capítulo N/archivo.zip
 *   - Archivo directo: RAWs/Cap_N.zip  (subida manual)
 */
function buscarCapituloConEnlaces(proyDriveId, capNum, etapaFiltro) {
  try {
    var capInt     = parseInt(capNum);
    var capNombre  = 'Capítulo ' + capNum;
    var capNombreB = 'Capitulo '  + capNum;
    var etapasMap  = {
      raw:   '01. RAWs',
      trad:  '02. Traducción',
      clean: '03. Limpieza y Redibujo',
      type:  '04. Typos',
      proof: '05. Control de Calidad'
    };
    var resultado  = {};
    var pFolder    = DriveApp.getFolderById(proyDriveId);

    Object.keys(etapasMap).forEach(function(clave) {
      var etapaName = etapasMap[clave];

      // Filtrar etapa si se pidió una específica
      if (etapaFiltro && etapaFiltro !== 'Todas' && etapaFiltro !== etapaName) {
        resultado[clave] = { encontrado: false };
        return;
      }

      try {
        var eFolders = pFolder.getFoldersByName(etapaName);
        if (!eFolders.hasNext()) { resultado[clave] = { encontrado: false }; return; }
        var eFolder = eFolders.next();

        if (clave === 'raw') {
          // 1) Buscar archivo directo: Cap_N.zip / Cap_N.rar / cap-N.zip etc.
          var files = eFolder.getFiles();
          while (files.hasNext()) {
            var f = files.next();
            var m = f.getName().toLowerCase().match(/^cap[_\-\s]?0*(\d+)/);
            if (m && parseInt(m[1]) === capInt) {
              resultado[clave] = { encontrado: true, id: f.getId(), nombre: f.getName() };
              return;
            }
          }
          // 2) Buscar subcarpeta "Capítulo N" (subidas por panel)
          var subFolders = [
            eFolder.getFoldersByName(capNombre),
            eFolder.getFoldersByName(capNombreB)
          ];
          for (var i = 0; i < subFolders.length; i++) {
            if (subFolders[i].hasNext()) {
              var capFiles = subFolders[i].next().getFiles();
              if (capFiles.hasNext()) {
                var cf = capFiles.next();
                resultado[clave] = { encontrado: true, id: cf.getId(), nombre: cf.getName() };
                return;
              }
            }
          }
          resultado[clave] = { encontrado: false };

        } else {
          // Otras etapas: buscar subcarpeta "Capítulo N"
          var subFolders = [
            eFolder.getFoldersByName(capNombre),
            eFolder.getFoldersByName(capNombreB)
          ];
          for (var i = 0; i < subFolders.length; i++) {
            if (subFolders[i].hasNext()) {
              var capFiles = subFolders[i].next().getFiles();
              if (capFiles.hasNext()) {
                var cf = capFiles.next();
                resultado[clave] = { encontrado: true, id: cf.getId(), nombre: cf.getName() };
                return;
              }
            }
          }
          resultado[clave] = { encontrado: false };
        }
      } catch(err) {
        resultado[clave] = { encontrado: false };
      }
    });

    return jsonResponse({ exito: true, etapas: resultado });
  } catch(err) {
    return jsonResponse({ exito: false, mensaje: 'Error: ' + err.toString() });
  }
}

function jsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
