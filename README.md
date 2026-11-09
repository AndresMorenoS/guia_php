# La Plantilla · CRUD de empleados (PHP + MySQL)

Ejercicio "MySQL - PHP" de Programación Web (UNIMINUTO). Valores en pesos colombianos (COP).

## Instalación en XAMPP
1. Enciende **Apache** y **MySQL** en el panel de XAMPP.
2. Copia la carpeta `la-plantilla/` en `C:\xampp\htdocs\`.
3. Abre phpMyAdmin → pestaña **Importar** → selecciona `sql/empresa.sql` (crea la base `empresa` con 9 empleados).
4. Revisa `config.php` (usuario, clave y `SALARIO_MINIMO`, hoy en $1.750.905 = SMMLV 2026, Decreto 1469 de 2025).
5. Abre `http://localhost/la-plantilla/`.

## Reglas principales
- Documento: 6 a 10 dígitos, único, no se puede cambiar al editar.
- Nombre 3–60 letras; domicilio 5–80 caracteres; sexo `f` o `m`.
- Nacimiento: mayor de edad, no futuro, máximo 100 años atrás. Ingreso: no futuro y con 18 años cumplidos ese día.
- Sueldo en COP: formato `1.250.500,50`, entre el salario mínimo y $99.999.999,99.
- Borrar solo por POST con token CSRF, tras una pantalla de confirmación.

> Los sueldos del enunciado (300 a 700) se escalaron a pesos para que sean coherentes con el mínimo actual; Oscar Mendez queda exactamente en el mínimo. Luis Luque y Alberto Soto incumplen la regla de 18 años al ingresar; se cargan tal cual y el formulario pedirá corregir la fecha al editarlos.

## Resultados esperados de las consultas
- Mayo: 1 ramo de rosas, 2 corbatas (Juan Perez, Ofelia Garcia, Oscar Mendez).
- Por sexo: Femenino 4, Masculino 5.
- Nómina: Femenino $12.200.000,00 · Masculino $14.480.905,00 · Total $26.680.905,00 COP.
- Aumento del 10 %: total actual $26.680.905,00 → nuevo $29.348.995,50 (simulación, no modifica la base).
- Ganan más del mínimo ($1.750.905): 8 (Oscar Mendez gana exactamente el mínimo y no cuenta).
