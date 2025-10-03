<?php
// auth.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* =========================
   Sesión / helpers básicos
   ========================= */
function is_logged_in(): bool {
  return isset($_SESSION['usuario']);
}

/* Login URL robusto según ubicación actual (root o subcarpetas) */
function _login_href(): string {
  $self = $_SERVER['PHP_SELF'] ?? '';
  // si estamos en /secciones/* o /api/* -> usar ../login/
  if (str_contains($self, '/secciones/') || str_contains($self, '/api/')) {
    return '../login/login.html';
  }
  return 'login/login.html';
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: ' . _login_href());
    exit();
  }
}

/* =========================
   Rol de la sesión
   ========================= */
function current_role_raw() {
  // Ajustá keys si tu login guarda el rol en otra estructura
  return $_SESSION['rol'] ?? ($_SESSION['user']['rol'] ?? null);
}

function current_role(): ?string {
  $raw = current_role_raw();
  if ($raw === null) return null;
  $rol = mb_strtolower(trim((string)$raw));
  // quitar tildes (por si viene “Coordinadores”, etc.)
  return strtr($rol, [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
    'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u'
  ]);
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
function _is_api_context(): bool {
  $sn = $_SERVER['SCRIPT_NAME'] ?? '';
  return str_contains($sn, '/api/');
}
function _block_stj_if_writes(): void {
  $role = current_role();
  if ($role !== 'stj') return;

  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $accion = strtolower($_POST['accion'] ?? $_GET['accion'] ?? '');

  // Acciones de escritura que a veces llegan por GET (compat)
  $write_actions = [
    'guardar','grabar','insert','alta','update','editar','modificar',
    'finalizar','reabrir','eliminar','borrar','delete','destroy',
    'remove','upload','subir','pin','fijar','desfijar'
  ];

  $tries_to_write = _is_post_like($method) || !empty($_FILES) || in_array($accion, $write_actions, true);
  if ($tries_to_write) {
    http_response_code(403);
    if (_is_api_context()) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'Solo lectura (STJ)']);
    } else {
      echo '<h3 style="font-family:sans-serif;color:#7c1c2c;margin:2rem">Solo lectura (STJ)</h3>';
    }
    exit;
  }
}

/* =========================
   Acceso por rol a rutas
   ========================= */
function enforce_route_access(): void {
  // Ignorá assets (solo controlamos PHP)
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
  $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  if ($ext !== '' && $ext !== 'php') return;

  $role = current_role();

  // STJ: puede ver TODO, pero se bloquea cualquier intento de escritura
  if ($role === 'stj') {
    _block_stj_if_writes();
    return; // lectura permitida
  }

  // COORDINADOR: solo Home y Coordinación (y login/logout)
  if (is_coordinator()) {
    // Normalizamos el script en formato relativo sin slash inicial
    $script = ltrim(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH), '/');

    // Whitelist estricta para coordinador
    $allowed = [
      'index.php',
      'secciones/coordinacion.php',
      // Login/Logout
      'login/login.php','login/login.html','login/logout.php',
      // (Opcional) Debug temporal de rol: QUITAR cuando termines
      'tools/rol-debug.php',
      // (Si Coordinación usa APIs propias, agregalas aquí)
      // 'api/api-coordinacion.php',
    ];

    if (!in_array($script, $allowed, true)) {
      http_response_code(403);
      echo 'Acceso restringido para coordinadores.';
      exit;
    }
  }

  // secretaria (u otros): acceso completo
}

/* =========================
   Permisos por módulo (tu lógica)
   ========================= */
function can_write_module(string $module): bool {
  $r = current_role();
  if ($r === 'secretaria') return true;                   // full
  if (is_coordinator())   return ($module === 'coordinacion'); // solo Coordinación
  // stj (u otro): solo lectura
  return false;
}

/** Corta en 403 (JSON) si no puede escribir en el módulo */
function ensure_can_write(string $module): void {
  if (!can_write_module($module)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sin permisos para esta acción']);
    exit;
  }
}

/** Útil para la UI (ocultar/desactivar edición según módulo) */
function can_edit_ui(string $module): bool {
  return can_write_module($module);
}

/* =========================
   (Opcional) Inyectar bloqueo visual para STJ
   Llamalo en páginas con formularios: render_readonly_ui();
   ========================= */
function render_readonly_ui(): void {
  if (current_role() !== 'stj') return;
  echo <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Bloquear envíos
  document.querySelectorAll('form').forEach(f=>{
    f.addEventListener('submit', e => { e.preventDefault(); e.stopImmediatePropagation(); return false; });
  });
  // Desactivar inputs
  document.querySelectorAll('input, textarea, select').forEach(el=>{
    el.setAttribute('readonly','readonly');
    if (el.tagName!=='INPUT' || !['radio','checkbox','hidden'].includes((el.type||'').toLowerCase())){
      el.setAttribute('disabled','disabled');
    }
  });
  // Desactivar botones y links de acción
  const sel = [
    'button','input[type=submit]','a.btn-danger','a.btn-warning','a.btn-primary',
    'a[href*="eliminar"]','a[href*="borrar"]','a[href*="editar"]','a[href*="guardar"]',
    '.btn-save','.btn-upload','.btn-delete'
  ].join(',');
  document.querySelectorAll(sel).forEach(el=>{
    el.setAttribute('disabled','disabled');
    el.style.pointerEvents = 'none';
    el.title = 'Solo lectura (STJ)';
    el.classList.add('disabled');
  });
});
</script>
HTML;
}
