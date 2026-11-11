<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Spanish strings.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aulaet37:managetimetable'] = 'Editar el horario del aula';
$string['aulaet37:viewtimetables'] = 'Ver el horario personal de los estudiantes del aula';
$string['coursetimetable'] = 'Horario del aula';
$string['datafieldsmissing'] = 'Faltan los campos de perfil DNI y fecha de nacimiento. Pedile a un administrador que ejecute la configuración de Aula ET37.';
$string['datasaved'] = 'Listo, tus datos quedaron guardados.';
$string['datastep'] = 'Completá tus datos';
$string['datastepintro'] = 'Hola, {$a}. La escuela necesita tu DNI y tu fecha de nacimiento para identificarte. Solo los ven vos y el personal autorizado.';
$string['datastepkicker'] = 'Antes de empezar';
$string['dnirequired'] = 'Escribí tu DNI.';
$string['field_birthdate'] = 'Fecha de nacimiento';
$string['field_dni'] = 'DNI';
$string['field_dni_desc'] = 'Documento Nacional de Identidad. Escribí solo números, sin puntos.';
$string['field_github'] = 'Usuario de GitHub';
$string['field_github_desc'] = 'Tu nombre de usuario de GitHub (por ejemplo, octocat), para que tus docentes encuentren tus repositorios. Es opcional.';
$string['invalidbirthdate'] = 'Elegí tu fecha de nacimiento real: la edad debe estar entre {$a->min} y {$a->max} años.';
$string['invaliddni'] = 'Escribí el DNI solo con números, sin puntos ni espacios (7 u 8 dígitos).';
$string['mytimetable'] = 'Mi horario';
$string['mytimetableintro'] = 'Tus clases de la semana, armadas con las aulas en las que estás matriculado/a. Si falta una materia, avisale a tu preceptor/a.';
$string['newsforumintro'] = 'Comunicados de la escuela para toda la comunidad. Cada aviso llega como notificación y por correo a todas las cuentas.';
$string['newsforumname'] = 'Avisos de la escuela';
$string['noclasses'] = 'Sin clases';
$string['noclassestoday'] = 'Hoy no hay clases cargadas.';
$string['nocoursetimetable'] = 'Esta aula todavía no tiene horario cargado.';
$string['nostudents'] = 'No hay estudiantes matriculados.';
$string['notimetable'] = 'Todavía no hay clases cargadas en estas aulas. Cuando el equipo docente o preceptoría cargue el horario, aparece acá.';
$string['now'] = 'Ahora';
$string['paneladddate'] = 'Agregar fecha';
$string['panelalldates'] = 'Ver calendario';
$string['panelallnews'] = 'Ver todos los avisos';
$string['paneldates'] = 'Fechas de la escuela';
$string['panelfulltimetable'] = 'Ver horario completo';
$string['panelnews'] = 'Avisos de la escuela';
$string['panelnodates'] = 'No hay fechas próximas.';
$string['panelnonews'] = 'No hay avisos por ahora.';
$string['panelpostnews'] = 'Publicar aviso';
$string['photo'] = 'Foto de perfil';
$string['photodisabled'] = 'Las fotos de perfil están desactivadas en este sitio.';
$string['photonotprocessed'] = 'No se pudo procesar la imagen. Probá con otra foto JPG o PNG.';
$string['photorequired'] = 'Subí una foto para continuar.';
$string['photosaved'] = '¡Listo! Tu foto de perfil quedó guardada.';
$string['photostep'] = 'Subí tu foto de perfil';
$string['photostepintro'] = 'Hola, {$a}. Para que docentes y compañeros te reconozcan, necesitamos una foto antes de entrar al aula.';
$string['photostepkicker'] = 'Último paso';
$string['photosteptip1'] = 'Que se vea bien tu cara, de frente y con buena luz.';
$string['photosteptip2'] = 'Formato JPG o PNG. Podés arrastrar el archivo al recuadro.';
$string['photosteptip3'] = 'Sin otras personas ni datos personales visibles en la imagen.';
$string['pluginname'] = 'Aula ET37: registro y perfil';
$string['privacy:metadata'] = 'Este complemento no guarda datos personales propios: DNI, fecha de nacimiento, usuario de GitHub y foto se almacenan en los campos de perfil y la imagen de usuario de Moodle, y el horario se guarda por aula (no por persona).';
$string['profilecategory'] = 'Datos personales';
$string['requirephoto'] = 'Exigir foto de perfil';
$string['requirephoto_desc'] = 'Las cuentas creadas por autorregistro o Google deben subir una foto al ingresar por primera vez (después de completar DNI y fecha de nacimiento, si faltan). No se aplica a administradores ni a cuentas manuales.';
$string['savedata'] = 'Guardar y continuar';
$string['savephoto'] = 'Guardar foto y continuar';
$string['signupphotonotice'] = 'Después de confirmar tu cuenta, al ingresar por primera vez te vamos a pedir una foto de perfil.';
$string['signupusername'] = 'Nombre de usuario';
$string['signupusername_help'] = 'Elegí un nombre corto, en minúsculas y sin espacios (por ejemplo, nombre.apellido). También vas a poder entrar con tu correo.';
$string['slotadd'] = 'Agregar bloque';
$string['slotallgroups'] = 'Todos los estudiantes del aula';
$string['slotdeleteconfirm'] = '¿Borrar el bloque del {$a}?';
$string['slotdeleted'] = 'Bloque borrado.';
$string['slotedit'] = 'Editar bloque';
$string['slotend'] = 'Termina';
$string['slotendbeforestart'] = 'La hora de fin tiene que ser posterior a la de inicio.';
$string['slotgroup'] = 'Para';
$string['slotgroup_help'] = 'Si el aula reúne varias divisiones o subgrupos (grupos de Moodle), elegí a cuál corresponde este bloque. Solo lo ven los integrantes de ese grupo.';
$string['slotinvalidtime'] = 'Escribí la hora como HH:MM (por ejemplo, 07:45).';
$string['slotnotes'] = 'Observaciones';
$string['slotoutofday'] = 'El bloque tiene que estar entre las {$a->from} y las {$a->to}.';
$string['slotroom'] = 'Aula, laboratorio o taller';
$string['slotsaved'] = 'Horario guardado.';
$string['slotstart'] = 'Empieza';
$string['slottimehelp'] = 'Formato de 24 horas, HH:MM.';
$string['slotweekday'] = 'Día';
$string['studenttimetables'] = 'Horarios de los estudiantes';
$string['studenttimetablesintro'] = 'El horario personal de cada estudiante reúne todas sus aulas, no solo esta.';
$string['teachers'] = 'Docentes';
$string['timetable'] = 'Horario de clases';
$string['timetableof'] = 'Horario de {$a}';
$string['timetableofintro'] = 'Clases de la semana de {$a}, según las aulas en las que está matriculado/a.';
$string['today'] = 'Hoy';
$string['todayclasses'] = 'Hoy, {$a}';
$string['weekview'] = 'Semana';
