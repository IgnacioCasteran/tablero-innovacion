<?php
// auth.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* =========================
   Sesión / helpers básicos
   ========================= */
function is_logged_in(): bool { return isset($_SESSION['usuario']); }

function _login_href(): string {
  $self = $_SERVER['PHP_SELF'] ?? '';
  if (str_contains($self, '/secciones/') || str_contains($self, '/api/')) return '../login/login.html';
  return 'login/login.html';
}

function require_login(): void {
  if (!is_logged_in()) { header('Location: ' . _login_href()); exit(); }
}

/* =========================
   Rol de la sesión
   ========================= */
function current_role_raw() { return $_SESSION['rol'] ?? ($_SESSION['user']['rol'] ?? null); }

function current_role(): ?string {
  $raw = current_role_raw(); if ($raw === null) return null;
  $rol = mb_strtolower(trim((string)$raw));
  return strtr($rol, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u']);
}

function is_coordinator(): bool {
  $r = current_role();
  return ($r === 'coordinador' || $r === 'coordinadores' || strpos((string)$r, 'coordinador') === 0);
}

/* =========================
   Solo lectura para STJ
   ========================= */
function _is_post_like(string $m = null): bool {
  $m = strtoupper($m ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
  return !in_array($m, ['GET','HEAD','OPTIONS'], true);
}
function _is_api_context(): bool { $sn = $_SERVER['SCRIPT_NAME'] ?? ''; return str_contains($sn, '/api/'); }
function _block_stj_if_writes(): void {
  if (current_role() !== 'stj') return;
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $accion = strtolower($_POST['accion'] ?? $_GET['accion'] ?? '');
  $write_actions = ['guardar','grabar','insert','alta','update','editar','modificar','finalizar','reabrir','eliminar','borrar','delete','destroy','remove','upload','subir','pin','fijar','desfijar'];
  $tries = _is_post_like($method) || !empty($_FILES) || in_array($accion, $write_actions, true);
  if ($tries) {
    http_response_code(403);
    if (_is_api_context()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error'=>'Solo lectura (STJ)']); }
    else { echo '<h3 style="font-family:sans-serif;color:#7c1c2c;margin:2rem">Solo lectura (STJ)</h3>'; }
    exit;
  }
}

/* =========================
   Acceso por rol a rutas
   ========================= */
function enforce_route_access(): void {
  // Solo PHP (ignorar assets)
  $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
  $ext = strtolower(pathinfo($reqPath, PATHINFO_EXTENSION));
  if ($ext !== '' && $ext !== 'php') return;

  $role = current_role();

  // STJ: lectura permitida, escrituras bloqueadas
  if ($role === 'stj') { _block_stj_if_writes(); return; }

  // === Coordinador: whitelist estricta ===
  $r = mb_strtolower((string)$role);
  $r = strtr($r, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u']);
  $isCoord = ($r === 'coordinador' || $r === 'coordinadores' || strpos($r, 'coordinador') === 0);

  if ($isCoord) {
    $scriptPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?? '';
    $scriptPath = ltrim($scriptPath, '/');
    $scriptFile = basename($scriptPath);

    $allowFiles = [
      // Home y autenticación
      'index.php','login.php','login.html','logout.php',
      'login/login.php','login/login.html','login/logout.php',

      // Coordinación + circunscripciones/oficinas
      'coordinacion.php',
      'circunscripcion1.php','circunscripcion2.php','circunscripcion3.php','circunscripcion4.php',
      'oficina-penal1.php','oficina-civil1.php','oficina-familia1.php','oficina-ejecucion-cyq1.php',
      'oficina-penal2.php','oficina-familia2.php',
      'oficina-penal3-acha.php','oficina-penal3-25mayo.php',
      'oficina-penal4.php',

      // ✅ Nuevas vistas habilitadas para coordinador
      'normativa.php',
      'objetos-secuestrados.php',
      'oficinas-judiciales.php',

      // Informes (ver/editar si corresponde)
      'informe-registrados.php','carga_informe.php','editar_informe.php',
      'guardar_informe.php','eliminar_informe.php','obtener_informes.php',
    ];

    // Aceptar también secciones/<archivo>
    $allowPaths = array_merge($allowFiles, array_map(fn($f) => "secciones/$f", $allowFiles));
    $ok = in_array($scriptFile, $allowFiles, true) || in_array($scriptPath, $allowPaths, true);

    if (!$ok) { http_response_code(403); echo 'Acceso restringido para coordinadores.'; exit; }
  }
  // Otros roles: acceso completo
}

/* =========================
   Permisos por módulo (tu lógica)
   ========================= */
function can_write_module(string $module): bool {
  $r = current_role();
  if ($r === 'secretaria') return true;
  if (is_coordinator())   return ($module === 'coordinacion');
  return false;
}

function ensure_can_write(string $module): void {
  if (!can_write_module($module)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sin permisos para esta acción']);
    exit;
  }
}

function can_edit_ui(string $module): bool { return can_write_module($module); }

/* =========================
   (Opcional) Bloqueo visual para STJ
   ========================= */
function render_readonly_ui(): void {
  if (current_role() !== 'stj') return;
  echo <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('form').forEach(f=>{ f.addEventListener('submit', e=>{ e.preventDefault(); e.stopImmediatePropagation(); return false; }); });
  document.querySelectorAll('input, textarea, select').forEach(el=>{
    el.setAttribute('readonly','readonly');
    if (el.tagName!=='INPUT' || !['radio','checkbox','hidden'].includes((el.type||'').toLowerCase())) el.setAttribute('disabled','disabled');
  });
  const sel=['button','input[type=submit]','a.btn-danger','a.btn-warning','a.btn-primary','a[href*="eliminar"]','a[href*="borrar"]','a[href*="editar"]','a[href*="guardar"]','.btn-save','.btn-upload','.btn-delete'].join(',');
  document.querySelectorAll(sel).forEach(el=>{ el.setAttribute('disabled','disabled'); el.style.pointerEvents='none'; el.title='Solo lectura (STJ)'; el.classList.add('disabled'); });
});
</script>
HTML;
}

