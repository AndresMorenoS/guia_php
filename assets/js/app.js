(function () {
    'use strict';

    var reducido = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // --- Validación en vivo (mismas reglas y mensajes que el servidor) ---
    function formatoSueldo(t) {
        t = t.replace(/[$\s]/g, '');
        if (!/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/.test(t) && !/^\d+(,\d{1,2})?$/.test(t)) { return null; }
        return parseFloat(t.replace(/\./g, '').replace(',', '.'));
    }
    function dinero(v) {
        return '$' + v.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' COP';
    }
    function aniosEntre(desde, hasta) {
        var a = hasta.getFullYear() - desde.getFullYear();
        var m = hasta.getMonth() - desde.getMonth();
        if (m < 0 || (m === 0 && hasta.getDate() < desde.getDate())) { a--; }
        return a;
    }
    function parseFecha(s) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (!m) { return null; }
        var d = new Date(+m[1], +m[2] - 1, +m[3]);
        return (d.getFullYear() === +m[1] && d.getMonth() === +m[2] - 1 && d.getDate() === +m[3]) ? d : null;
    }

    var reglas = {
        documento: function (v, f) {
            if (f.documento.readOnly) { return ''; }
            if (!v) { return 'Escribe el documento.'; }
            if (!/^\d+$/.test(v)) { return 'Solo se permiten números.'; }
            if (v.length < 6 || v.length > 10) { return 'Debe tener entre 6 y 10 dígitos.'; }
            return '';
        },
        nombre: function (v) {
            v = v.trim().replace(/\s+/g, ' ');
            if (!v) { return 'Escribe el nombre.'; }
            if (v.length < 3 || v.length > 60) { return 'Debe tener entre 3 y 60 caracteres.'; }
            if (!/^[\p{L}' -]+$/u.test(v)) { return "Usa solo letras y espacios (se permiten ' y -)."; }
            return '';
        },
        sexo: function (v) { return (v === 'f' || v === 'm') ? '' : 'Selecciona el sexo.'; },
        domicilio: function (v) {
            v = v.trim();
            if (!v) { return 'Escribe el domicilio.'; }
            if (v.length < 5 || v.length > 80) { return 'Debe tener entre 5 y 80 caracteres.'; }
            if (!/^[\p{L}\p{N} #\-.,°]+$/u.test(v)) { return 'Usa solo letras, números, espacios y # - . , °'; }
            return '';
        },
        fechanacimiento: function (v) {
            var d = parseFecha(v), hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            if (!d) { return 'Escribe una fecha válida.'; }
            if (d > hoy) { return 'La fecha de nacimiento no puede ser futura.'; }
            var edad = aniosEntre(d, hoy);
            var limite = new Date(hoy.getFullYear() - 100, hoy.getMonth(), hoy.getDate());
            if (d < limite) { return 'La fecha de nacimiento no puede ser de hace más de 100 años.'; }
            if (edad < 18) { return 'El empleado debe ser mayor de edad.'; }
            return '';
        },
        fechaingreso: function (v, f) {
            var d = parseFecha(v), hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            if (!d) { return 'Escribe una fecha válida.'; }
            if (d > hoy) { return 'La fecha de ingreso no puede ser futura.'; }
            var nac = parseFecha(f.fechanacimiento.value);
            if (nac && nac <= hoy && aniosEntre(nac, hoy) >= 18 && aniosEntre(nac, d) < 18) {
                return 'El empleado debía tener 18 años cumplidos al ingresar.';
            }
            return '';
        },
        sueldobasico: function (v) {
            v = v.trim();
            if (!v) { return 'Escribe el sueldo.'; }
            var n = formatoSueldo(v);
            if (n === null) { return 'Usa el formato 1.250.500,50 (coma para decimales).'; }
            if (n < window.SALARIO_MINIMO) { return 'El sueldo no puede ser menor al salario mínimo (' + dinero(window.SALARIO_MINIMO) + ').'; }
            if (n > 99999999.99) { return 'El sueldo no puede superar ' + dinero(99999999.99) + '.'; }
            return '';
        }
    };

    var form = document.querySelector('form.formulario');
    if (form) {
        var mostrar = function (campo, msg) {
            var caja = campo.closest('.campo');
            var prev = caja.querySelector('.error-campo[data-js]');
            if (prev) { prev.remove(); }
            caja.classList.remove('valido', 'invalido', 'con-error');
            campo.removeAttribute('aria-invalid');
            if (msg) {
                caja.classList.add('invalido');
                campo.setAttribute('aria-invalid', 'true');
                var p = document.createElement('small');
                p.className = 'error-text error-campo';
                p.setAttribute('data-js', '');
                p.id = campo.id + '-error-js';
                p.textContent = msg;
                caja.appendChild(p);
            } else if (campo.value && !campo.readOnly) {
                caja.classList.add('valido');
            }
        };
        Object.keys(reglas).forEach(function (nombre) {
            var campo = form.elements[nombre];
            if (!campo || !campo.id) { campo = form.querySelector('#' + nombre); }
            if (!campo) { return; }
            campo.addEventListener('blur', function () { mostrar(campo, reglas[nombre](campo.value, form.elements)); });
        });

        // Contadores
        form.querySelectorAll('.contador').forEach(function (c) {
            var campo = document.getElementById(c.getAttribute('data-para'));
            var max = c.getAttribute('data-max');
            var act = function () { c.textContent = campo.value.length + '/' + max; };
            campo.addEventListener('input', act);
            act();
        });

        // Foco en el primer error del servidor
        var primero = form.querySelector('[aria-invalid="true"]');
        if (primero) { primero.focus(); }
    }

    // --- Conteo animado ---
    document.querySelectorAll('[data-valor]').forEach(function (el) {
        var fin = parseInt(el.getAttribute('data-valor'), 10);
        if (reducido || !fin) { return; }
        var t0 = null;
        function paso(t) {
            if (t0 === null) { t0 = t; }
            var p = Math.min((t - t0) / 700, 1);
            el.textContent = Math.round(fin * p);
            if (p < 1) { requestAnimationFrame(paso); }
        }
        el.textContent = '0';
        requestAnimationFrame(paso);
    });

    // --- Selector de mes ---
    document.querySelectorAll('select[data-autoenviar]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });

    var mes = document.getElementById('mes');
    if (mes && mes.form) {
        mes.addEventListener('change', function () { mes.form.submit(); });
    }
})();
