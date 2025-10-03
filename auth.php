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
   Normalización de rol
   ========================= */
/** Devuelve el rol “crudo” guardado en sesión (sin tocar). Puede ser string o array. */
function current_role_raw(): mixed {
  return $_SESSION['rol'] ?? null; // lo carga el login/SSO
}

/** Normaliza string: minúsculas, sin tildes. */
function _normstr(?string $s): string {
  $s = mb_strtolower(trim((string)$s));
  // quitar acentos / dieresis
  $s = strtr($s, [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
    'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
    'ñ'=>'n',
  ]);
  return $s;
}

/**
 * Devuelve rol normalizado para toda la app:
 *   'secretaria' | 'coordinador' | 'stj' | (otro literal normalizado si no matchea)
 * Acepta variantes tipo “Coordinadores La Pampa”, “Coordinación…”, etc.
 */
function current_role(): ?string {
  $raw = current_role_raw();

  // si viene array de grupos/roles, probamos aplanar
  if (is_array($raw)) {
    // preferimos el que contenga “coordinador”, “secretar”, “stj”
    foreach ($raw as $r) {
      $n = _normstr((string)$r);
      if (str_contains($n,'coordinador')) return 'coordinador';
      if (str_starts_with($n,'secretar'))  return 'secretaria';
      if (str_starts_with($n,'stj') || $n==='stj') return 'stj';
    }
    // si no, nos quedamos con el primero
    $raw = reset($raw);
  }

  if ($raw === null) return null;
  $n = _normstr((string)$raw);

  if (str_contains($n,'coordinador')) return 'coordinador'; // “coordinadores…”, “coordinacion…”
  if (str_starts_with($n,'secretar'))  return 'secretaria'; // “secretaria/o…”
  if ($n === 'stj' || str_starts_with($n,'stj')) return 'stj';

  // por si viene algo desconocido, devolvemos lo normalizado
  return $n ?: null;
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
  $role = current_role();
  $script = basename($_SERVER['SCRIPT_NAME'] ?? '');

  // STJ: puede ver TODO, pero se bloquea cualquier intento de escritura
  if ($role === 'stj') {
    _block_stj_if_writes();
    return; // lectura permitida
  }

  // COORDINADOR: restringir navegación
  if ($role === 'coordinador') {
    // Páginas permitidas para COORDINADOR (sin SGC, sin Proyectos, etc.)
    $allowed = [
      // ===== Informes (ver/editar) =====
      'informe-registrados.php',
      'carga_informe.php',
      'editar_informe.php',
      'guardar_informe.php',
      'eliminar_informe.php',
      'obtener_informes.php',

      // ===== Secciones habilitadas =====
      'coordinacion.php',
      'circunscripcion1.php',
      'oficina-penal1.php',
      'oficina-civil1.php',
      'oficina-familia1.php',
      'oficina-ejecucion-cyq1.php',
      'circunscripcion2.php',
      'oficina-penal2.php',
      'oficina-familia2.php',
      'circunscripcion3.php',
      'oficina-penal3-acha.php',
      'oficina-penal3-25mayo.php',
      'circunscripcion4.php',
      'oficina-penal4.php',

      // ===== Navegación básica =====
      'index.php', 'login.php', 'login.html', 'logout.php',
    ];

    if (!in_array($script, $allowed, true)) {
      http_response_code(403);
      if (_is_api_context()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Acceso restringido para coordinadores.']);
      } else {
        echo 'Acceso restringido para coordinadores.';
      }
      exit;
    }
  }

  // secretaria: acceso completo
  // otros roles no mapeados: acceso por defecto (ajustá si querés endurecer)
}

/* =========================
   Permisos por módulo (tu lógica)
   ========================= */
function can_write_module(string $module): bool {
  $r = current_role();
  if ($r === 'secretaria')  return true;                    // full
  if ($r === 'coordinador') return ($module === 'informes'); // sólo Informes
  // stj u otros: solo lectura
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
   (Opcional) Bloqueo visual para STJ
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
