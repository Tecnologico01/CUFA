<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('crear_docentes');

$error = '';
$mensaje = '';
$credenciales = '';

/* =========================
   FUNCIONES AUXILIARES
========================= */

function limpiarTexto($texto){

    $texto = strtoupper(trim($texto));

    $buscar = [
        'Á','É','Í','Ó','Ú','Ñ'
    ];

    $reemplazar = [
        'A','E','I','O','U','N'
    ];

    return str_replace(
        $buscar,
        $reemplazar,
        $texto
    );
}

function generarUsername($nombres, $apellido){

    $base =
        strtolower(substr($nombres,0,1)) .
        strtolower(limpiarTexto($apellido));

    return preg_replace(
        '/[^a-z0-9]/',
        '',
        $base
    ) . rand(10,99);
}

function subirArchivo($file, $ruta, $prefijo){

    if(
        isset($file['error']) &&
        $file['error'] === 0
    ){

        $ext = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        $permitidos = [
            'pdf','jpg','jpeg','png'
        ];

        if(!in_array($ext, $permitidos)){
            return null;
        }

        $nombre =
            $prefijo .
            '_' .
            time() .
            '_' .
            rand(100,999) .
            '.' .
            $ext;

        if(
            move_uploaded_file(
                $file['tmp_name'],
                $ruta . $nombre
            )
        ){

            return 'uploads/docentes/' . $nombre;
        }
    }

    return null;
}

/* =========================
   OBTENER ROL DOCENTE
========================= */

$stmtRol = $pdo->prepare("
    SELECT id
    FROM roles
    WHERE slug = 'docente'
    LIMIT 1
");

$stmtRol->execute();

$rol_docente_id = $stmtRol->fetchColumn();

if(!$rol_docente_id){

    die("No existe el rol docente.");
}

/* =========================
   PROCESAR FORMULARIO
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    try{

        $pdo->beginTransaction();

        $nombres = trim($_POST['nombres']);
        $ap_p = trim($_POST['apellido_paterno']);
        $ap_m = trim($_POST['apellido_materno']);

        $email = trim($_POST['email']);

        /* =========================
           VALIDAR EMAIL DUPLICADO
        ========================= */

        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if($stmt->fetch()){

            throw new Exception(
                "El correo ya está registrado."
            );
        }

        /* =========================
           GENERAR CREDENCIALES
        ========================= */

        $username = generarUsername(
            $nombres,
            $ap_p
        );

        $password = trim($_POST['password']);

        $numero_identificador =
            'DOC' . rand(1000,9999);

        /* =========================
           CREAR USUARIO
        ========================= */

        $stmtUsuario = $pdo->prepare("
            INSERT INTO usuarios
            (
                nombres,
                apellido_paterno,
                apellido_materno,
                username,
                email,
                password_hash,
                rol_id,
                numero_identificador,
                activo
            )
            VALUES
            (
                ?,?,?,?,?,?,?,?,1
            )
        ");

        $stmtUsuario->execute([
            $nombres,
            $ap_p,
            $ap_m,
            $username,
            $email,
            password_hash(
                $password,
                PASSWORD_DEFAULT
            ),
            $rol_docente_id,
            $numero_identificador
        ]);

        $usuario_id =
            $pdo->lastInsertId();

        /* =========================
           CREAR DOCENTE
        ========================= */

        $stmtDocente = $pdo->prepare("
            INSERT INTO docentes
            (
                usuario_id,
                rfc,
                curp,
                direccion,
                fecha_contratacion,
                licenciatura,
                licenciatura_titulo,
                licenciatura_cedula,
                maestria,
                maestria_titulo,
                maestria_cedula,
                doctorado,
                doctorado_titulo,
                doctorado_cedula
            )
            VALUES
            (
                ?,?,?,?,
                NOW(),
                ?,?,?,?,?,?,?,?,?
            )
        ");

        $stmtDocente->execute([

            $usuario_id,

            strtoupper($_POST['rfc']),
            strtoupper($_POST['curp']),

            $_POST['direccion'],

            $_POST['licenciatura'],
            $_POST['lic_titulo_folio'],
            $_POST['lic_cedula_folio'],

            $_POST['maestria'],
            $_POST['mae_titulo_folio'],
            $_POST['mae_cedula_folio'],

            $_POST['doctorado'],
            $_POST['doc_titulo_folio'],
            $_POST['doc_cedula_folio']
        ]);

        $docente_id =
            $pdo->lastInsertId();

        /* =========================
           SUBIR DOCUMENTOS
        ========================= */

        $upload_dir =
            __DIR__ .
            '/../../uploads/docentes/';

        if(!is_dir($upload_dir)){

            mkdir(
                $upload_dir,
                0777,
                true
            );
        }

        $stmtDocumento = $pdo->prepare("
            INSERT INTO documentos_docente
            (
                docente_id,
                tipo,
                archivo
            )
            VALUES
            (
                ?,?,?
            )
        ");

        foreach($_FILES as $tipo => $file){

            $ruta = subirArchivo(
                $file,
                $upload_dir,
                $tipo
            );

            if($ruta){

                $stmtDocumento->execute([
                    $docente_id,
                    $tipo,
                    $ruta
                ]);
            }
        }

        $pdo->commit();

        $mensaje =
            "Docente registrado correctamente.";

        $credenciales =
            "Usuario: {$username}
             | Contraseña: {$password}";

    }catch(Exception $e){

        $pdo->rollBack();

        $error = $e->getMessage();
    }
}

?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen font-sans">
    
    <?php if($mensaje || $error): ?>
        <div class="mb-6 p-4 rounded-2xl shadow-lg <?= $error ? 'bg-red-50 border-l-4 border-red-500 text-red-800' : 'bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800' ?>">
            <p class="font-bold"><?= $error ?: $mensaje ?></p>
            <?php if($credenciales): ?><p class="text-xs mt-1"><?= $credenciales ?></p><?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-200 overflow-hidden">
                <div class="bg-indigo-600 px-8 py-5 text-white">
                    <h2 class="text-xs font-black uppercase tracking-widest text-center">Datos de Identidad</h2>
                </div>
                <div class="p-8 space-y-4">
                    <input name="nombres" id="nombres" type="text" placeholder="Nombres" required class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold">
                    <div class="grid grid-cols-2 gap-3">
                        <input name="apellido_paterno" id="ap_paterno" type="text" placeholder="Ap. Paterno" required class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold">
                        <input name="apellido_materno" id="ap_materno" type="text" placeholder="Ap. Materno" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">F. Nacimiento</label>
                            <input name="fecha_nacimiento" id="f_nacimiento" type="date" required class="w-full p-2 bg-indigo-50 border-2 border-indigo-100 rounded-xl text-xs font-bold text-indigo-700">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Sexo</label>
                            <select name="sexo" id="sexo" required class="w-full p-2 bg-indigo-50 border-2 border-indigo-100 rounded-xl text-xs font-bold text-indigo-700">
                                <option value="">-</option><option value="H">HOMBRE</option><option value="M">MUJER</option>
                            </select>
                        </div>
                    </div>

                    <select name="entidad" id="entidad" required class="w-full p-3 bg-indigo-50 border-2 border-indigo-100 rounded-xl text-[10px] font-bold text-indigo-700">
                        <option value="">ENTIDAD FEDERATIVA</option>
                        <option value="AS">Aguascalientes</option><option value="BC">Baja California</option><option value="BS">Baja California Sur</option>
                        <option value="CC">Campeche</option><option value="CS">Chiapas</option><option value="CH">Chihuahua</option>
                        <option value="CL">Coahuila</option><option value="CM">Colima</option><option value="DF">Ciudad de México</option>
                        <option value="DG">Durango</option><option value="GT">Guanajuato</option><option value="GR">Guerrero</option>
                        <option value="HG">Hidalgo</option><option value="JC">Jalisco</option><option value="MC">México</option>
                        <option value="MN">Michoacán</option><option value="MS">Morelos</option><option value="NT">Nayarit</option>
                        <option value="NL">Nuevo León</option><option value="OC">Oaxaca</option><option value="PL">Puebla</option>
                        <option value="QT">Querétaro</option><option value="QR">Quintana Roo</option><option value="SP">San Luis Potosí</option>
                        <option value="SL">Sinaloa</option><option value="SR">Sonora</option><option value="TC">Tabasco</option>
                        <option value="TS">Tamaulipas</option><option value="TL">Tlaxcala</option><option value="VZ">Veracruz</option>
                        <option value="YN">Yucatán</option><option value="ZS">Zacatecas</option><option value="NE">Extranjero</option>
                    </select>

                    <div class="space-y-3 pt-4 border-t">
                        <div class="relative">
                            <input name="curp" id="input_curp" type="text" placeholder="CURP" maxlength="18" required class="w-full p-3 font-mono font-bold text-indigo-600 bg-indigo-50 border-2 border-indigo-200 rounded-xl uppercase">
                            <p id="error_curp" class="text-[9px] text-red-500 mt-1 font-black hidden uppercase"></p>
                            <label class="file-label flex items-center gap-2 mt-2 p-2 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-all">
                                <span class="icon-status p-1 bg-slate-100 rounded text-slate-400"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="3"></path></svg></span>
                                <span class="status-text text-[9px] font-black text-slate-400 uppercase">SUBIR PDF CURP</span>
                                <input type="file" name="curp_doc" class="hidden">
                            </label>
                        </div>
                        
                        <div>
                            <input name="rfc" id="input_rfc" type="text" placeholder="RFC" maxlength="13" required class="w-full p-3 font-mono font-bold text-emerald-600 bg-emerald-50 border-2 border-emerald-200 rounded-xl uppercase">
                            <p id="error_rfc" class="text-[9px] text-red-500 mt-1 font-black hidden uppercase"></p>
                            <label class="file-label flex items-center gap-2 mt-2 p-2 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-all">
                                <span class="icon-status p-1 bg-slate-100 rounded text-slate-400"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="3"></path></svg></span>
                                <span class="status-text text-[9px] font-black text-slate-400 uppercase">SUBIR PDF RFC</span>
                                <input type="file" name="rfc_doc" class="hidden">
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="file-label border-2 border-dashed border-slate-200 rounded-2xl p-3 flex flex-col items-center justify-center hover:bg-slate-100 cursor-pointer transition-all">
                                <span class="status-text text-[8px] font-black text-slate-400 uppercase mb-1">PDF INE</span>
                                <div class="icon-status w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="4"></path></svg>
                                </div>
                                <input type="file" name="ine_doc" class="hidden" required>
                            </label>
                            <label class="file-label border-2 border-dashed border-slate-200 rounded-2xl p-3 flex flex-col items-center justify-center hover:bg-slate-100 cursor-pointer transition-all">
                                <span class="status-text text-[8px] font-black text-slate-400 uppercase mb-1">COMP. DOMICILIO</span>
                                <div class="icon-status w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="4"></path></svg>
                                </div>
                                <input type="file" name="domicilio_doc" class="hidden" required>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white space-y-4 shadow-2xl">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-800 pb-2">Ubicación y Acceso</h3>
                <textarea name="direccion" placeholder="Dirección Completa (Calle, #, Col, CP)" required class="w-full p-3 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 h-20 outline-none focus:ring-2 ring-indigo-500 transition-all"></textarea>
                
                <div class="space-y-3">
                    <input name="email" type="email" placeholder="Correo" required class="w-full p-3 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white outline-none focus:ring-2 ring-indigo-500">
                    <input name="password" type="password" placeholder="Contraseña" required class="w-full p-3 bg-slate-800 border border-slate-700 rounded-xl text-xs text-white outline-none focus:ring-2 ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="lg:col-span-8 space-y-6">
            <?php 
            $niveles = [
                ['id'=>'lic','nom'=>'Licenciatura','c'=>'blue','r'=>'required'],
                ['id'=>'mae','nom'=>'Maestría','c'=>'emerald','r'=>''],
                ['id'=>'doc','nom'=>'Doctorado','c'=>'purple','r'=>'']
            ];
            foreach($niveles as $n):
            ?>
            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-200 overflow-hidden">
                <div class="bg-<?= $n['c'] ?>-600 px-8 py-4 text-white flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-widest"><?= $n['nom'] ?></span>
                    <span class="text-[8px] bg-white/20 px-3 py-1 rounded-full font-bold uppercase"><?= $n['r'] ? 'Obligatorio' : 'Opcional' ?></span>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-12 gap-6">
                    <div class="md:col-span-5 space-y-3">
                        <input name="<?= ($n['id']=='lic'?'licenciatura':($n['id']=='mae'?'maestria':'doctorado')) ?>" type="text" placeholder="Título Obtenido" <?= $n['r'] ?> class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-sm font-bold">
                        <input name="<?= $n['id'] ?>_titulo_folio" type="text" placeholder="Folio del Título" <?= $n['r'] ?> class="w-full p-3 border-2 border-slate-100 rounded-xl text-xs">
                        <input name="<?= $n['id'] ?>_cedula_folio" type="text" placeholder="Folio de la Cédula" <?= $n['r'] ?> class="w-full p-3 border-2 border-slate-100 rounded-xl text-xs">
                    </div>
                    <div class="md:col-span-7 grid grid-cols-3 gap-2">
                        <?php foreach(['cert'=>'Certificado','titulo_doc'=>'Título PDF','cedula_doc'=>'Cédula PDF'] as $k=>$v): ?>
                        <label class="file-label border-2 border-dashed border-slate-200 rounded-2xl p-4 flex flex-col items-center justify-center hover:bg-<?= $n['c'] ?>-50 cursor-pointer transition-all">
                            <span class="status-text text-[8px] font-black text-slate-400 uppercase mb-2 text-center"><?= $v ?></span>
                            <div class="icon-status w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="4"></path></svg>
                            </div>
                            <input type="file" name="<?= $n['id'].'_'.$k ?>" class="hidden" <?= $n['r'] ?>>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="w-full py-6 bg-slate-900 text-white rounded-[2rem] font-black uppercase tracking-[0.3em] shadow-2xl hover:bg-black transition-all transform hover:-translate-y-1">Finalizar Registro de Expediente</button>
        </div>
    </form>
</div>

<script>
const cleanStr = (txt) => txt.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase().trim();

function getPrefix() {
    const n = cleanStr(document.getElementById('nombres').value);
    const p = cleanStr(document.getElementById('ap_paterno').value);
    const m = cleanStr(document.getElementById('ap_materno').value) || "X";
    if(n.length < 1 || p.length < 1) return "";
    const vocalMatch = p.substring(1).match(/[AEIOU]/);
    return p.charAt(0) + (vocalMatch ? vocalMatch[0] : "X") + m.charAt(0) + n.charAt(0);
}

function getDate() {
    const f = document.getElementById('f_nacimiento').value;
    return f ? f.split("-").map((v, i) => i === 0 ? v.substring(2) : v).join("") : "";
}

// Validaciones tiempo real CURP/RFC
['input_curp', 'input_rfc'].forEach(id => {
    document.getElementById(id).addEventListener('input', function(e) {
        const val = e.target.value.toUpperCase();
        const prefix = getPrefix();
        const date = getDate();
        const sex = document.getElementById('sexo').value;
        const ent = document.getElementById('entidad').value;
        const msg = document.getElementById(id === 'input_curp' ? 'error_curp' : 'error_rfc');
        
        let err = "";
        if (val.length >= 4 && val.substring(0, 4) !== prefix) err = "No coincide con el nombre.";
        else if (val.length >= 10 && date && val.substring(4, 10) !== date) err = "No coincide con la fecha.";
        if (id === 'input_curp') {
            if (val.length >= 11 && sex && val.charAt(10) !== sex) err = "No coincide el sexo.";
            else if (val.length >= 13 && ent && val.substring(11, 13) !== ent) err = "No coincide la entidad.";
        }

        if (err) {
            msg.textContent = "✘ " + err; msg.classList.remove('hidden');
            e.target.classList.add('border-red-500', 'bg-red-50');
        } else {
            msg.classList.add('hidden'); e.target.classList.remove('border-red-500', 'bg-red-50');
        }
    });
});

// Feedback visual de archivos
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const label = this.closest('.file-label');
        const statusText = label.querySelector('.status-text');
        const iconStatus = label.querySelector('.icon-status');

        if (this.files && this.files.length > 0) {
            const fileName = this.files[0].name;
            label.classList.remove('border-slate-200');
            label.classList.add('border-emerald-500', 'bg-emerald-50');
            statusText.textContent = "✓ " + (fileName.length > 12 ? fileName.substring(0, 9) + "..." : fileName);
            statusText.classList.add('text-emerald-700');
            iconStatus.classList.add('bg-emerald-500', 'text-white');
            iconStatus.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="4"></path></svg>';
        }
    });
});
</script>