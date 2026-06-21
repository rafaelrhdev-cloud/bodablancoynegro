# Invitación de Boda — Andrea & Mauricio (Plantilla)

Plantilla de invitación de boda digital en blanco y negro, elegante, con
animación de apertura tipo "puerta de catedral", confirmación de asistencia
(RSVP) con control de pases y panel privado para el organizador, con base
de datos MySQL.

---

## 1. Estructura del proyecto

```
wedding-invitation/
├── index.html            → Página principal (sitio público)
├── css/style.css
├── js/main.js
├── assets/images/         → Aquí van tus fotos reales
│
├── api/                   → Backend (PHP + MySQL)
│   ├── config.php         → Credenciales de la base de datos (edítalo)
│   ├── db.sql              → Script para crear las tablas
│   ├── check_name.php      → Valida el nombre del invitado
│   └── confirm.php         → Registra la confirmación de asistencia
│
└── admin/                 → Panel privado del organizador (oculto, sin enlaces públicos)
    ├── setup.php           → Crea tu usuario admin (usar 1 sola vez y luego borrar)
    ├── login.php
    ├── dashboard.php        → Aquí ves los invitados confirmados
    ├── logout.php
    └── assets/admin.css
```

Esta es ya la estructura final lista para subir: todo vive en un único nivel
de carpetas. **Súbela tal cual a la raíz pública de tu hosting** (la carpeta
que se suele llamar `public_html`, `www` o `htdocs`) o, si la subes a Git
primero, simplemente copia/clona el contenido completo de este repositorio
dentro de esa carpeta pública. No hace falta mover nada manualmente.

---

## 2. Instalación en tu hosting (paso a paso)

1. **Crea una base de datos MySQL** desde tu panel de hosting (cPanel, Plesk,
   etc.) y anota: nombre de la base, usuario y contraseña.

2. **Importa el esquema**: en phpMyAdmin (o la consola MySQL de tu hosting),
   importa el archivo `api/db.sql`. Esto crea las tablas `guests`,
   `confirmations`, `device_locks` y `admin_users`, además de 3 invitados de
   ejemplo (bórralos después desde el panel admin).

3. **Configura la conexión**: edita `api/config.php` y coloca tus datos
   reales:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'tu_base_de_datos');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_password');
   ```

4. **Sube los archivos** vía FTP/SFTP, Git, o el administrador de archivos
   del hosting. Como la estructura ya es plana, solo copia todo el contenido
   de esta carpeta dentro de la raíz pública de tu hosting.

5. **Crea tu usuario administrador**: abre en tu navegador
   `https://tudominio.com/admin/setup.php`, crea tu usuario y contraseña.
   Después de usarlo, **elimina ese archivo del servidor** (o renómbralo) por
   seguridad — el script se autobloquea solo, pero es buena práctica quitarlo.

6. **Agrega a tus invitados reales** desde
   `https://tudominio.com/admin/dashboard.php` (usa el formulario "Agregar
   invitación a la lista"). El nombre que escribas ahí es **exactamente** el
   nombre que tu invitado deberá escribir en el sitio para poder confirmar.

7. Reemplaza las fotos de ejemplo en `assets/images` y los textos en
   `index.html` (nombres, fecha, lugar, mesas de regalos, itinerario).

8. **¡Listo!** Comparte el enlace de tu dominio con tus invitados.

---

## 3. Reglas de negocio ya implementadas

- **Máximo 5 pases por invitación**, sin importar lo que se intente enviar
  desde el navegador (se valida también en el servidor).
- El número de pases real depende de lo que el organizador asignó a cada
  invitado (1 a 5), desde el panel admin.
- **Un dispositivo/navegador solo puede confirmar una vez**, sin importar el
  nombre que use (se guarda un identificador único en `localStorage` + cookie,
  y se valida también con una tabla `device_locks` en el servidor que es la
  verdadera barrera, ya que el control del navegador se puede borrar pero el
  servidor no se ve afectado por eso).
- El invitado **debe escribir un nombre previamente registrado por el
  organizador**; si no coincide, no puede avanzar.
- Una vez que una invitación confirma, queda marcada como `confirmed = 1` y no
  puede volver a confirmarse (ni desde otro dispositivo).

### Nota honesta sobre el límite "por dispositivo"
No existe una forma 100% infalible de identificar un dispositivo solo con
HTML/JS/PHP (alguien técnico podría usar modo incógnito o borrar cookies para
intentarlo de nuevo). Esta plantilla combina `localStorage` + cookie +
servidor para cubrir el 99% de los casos reales de una boda. Si necesitas un
control más estricto (por ejemplo ligado a verificación por WhatsApp o
teléfono), es una mejora adicional que se puede cotizar aparte.

---

## 4. Panel privado del organizador

La carpeta `/admin` **no tiene ningún enlace visible** en el sitio público:
solo quien conoce la URL puede entrar, y además requiere usuario y
contraseña. Ahí el organizador puede:

- Ver el total de invitaciones confirmadas y personas confirmadas.
- Ver el listado completo de quién confirmó, cuántos pases y cuándo.
- Agregar o quitar invitados de la lista permitida.

**Recomendación de seguridad extra:** renombra la carpeta `admin` a algo
único (ej. `panel-2026-xy7`) antes de publicar el sitio en producción, así
es aún más difícil de adivinar.

---

## 5. Personalización rápida

| Qué cambiar              | Dónde |
|---------------------------|-------|
| Nombres, fecha, textos     | `index.html` |
| Colores / tipografías      | `css/style.css` (sección `:root`) |
| Fotos de la galería        | `assets/images/` + CSS `.g1`–`.g6` |
| Itinerario                 | `index.html` sección `#itinerario` |
| Ligas de mesas de regalo    | `index.html` sección `#regalos` |

---

## 6. Este repositorio como producto

Esta carpeta está pensada para subirse a tu inventario de plantillas en Git
tal cual está: el front es independiente y reutilizable, y el backend
(`api/` + `admin/`) es genérico — cada cliente nuevo solo necesita su propia
base de datos y editar `api/config.php` con sus credenciales.
