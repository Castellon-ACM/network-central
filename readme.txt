=== Network Central ===
Contributors: Castellon-ACM
Tags: multisite, network, wp-config, htaccess, network setup
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Activa o desactiva WordPress Multisite con un solo toggle desde el escritorio de administración.

== Description ==

**Network Central** convierte una instalación normal de WordPress en una red Multisite (subdirectorios) con un único toggle, sin necesidad de editar archivos manualmente.

= Qué hace al activar =

* Escribe las 7 constantes necesarias en `wp-config.php` (`WP_ALLOW_MULTISITE`, `MULTISITE`, `SUBDOMAIN_INSTALL`, `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, `SITE_ID_CURRENT_SITE`, `BLOG_ID_CURRENT_SITE`)
* Reemplaza las reglas de reescritura de `.htaccess` con las reglas Multisite (subdirectorios)
* Crea las tablas de red en la base de datos (`wp_site`, `wp_blogs`, etc.) con `install_network()` y `populate_network()`
* Redirige automáticamente al Network Admin (`wp-admin/network.php`)

= Qué hace al desactivar =

* Elimina todas las constantes Multisite de `wp-config.php`
* Restaura las reglas de reescritura single-site en `.htaccess`

= Panel de estado =

La página muestra en tiempo real si Multisite está activo, si `wp-config.php` y `.htaccess` son escribibles, la versión de PHP y la versión de WordPress.

= Requisitos =

* PHP 7.4 o superior
* `wp-config.php` con permisos de escritura
* Servidor Apache (`.htaccess` activo) o capacidad de editar manualmente las reglas de reescritura en Nginx

== Installation ==

1. Sube la carpeta `network-central` a `/wp-content/plugins/`.
2. Activa el plugin desde **Plugins → Plugins instalados**.
3. Ve a **Network Central** en la barra lateral del escritorio.
4. Activa el toggle y pulsa **Guardar**.

== Frequently Asked Questions ==

= ¿Funciona con instalaciones en subdominios? =

No. Network Central configura únicamente la modalidad de **subdirectorios** (p. ej. `misitio.com/tienda/`). Si necesitas subdominios, edita `wp-config.php` manualmente y cambia `SUBDOMAIN_INSTALL` a `true`.

= ¿Qué pasa si `wp-config.php` no tiene permisos de escritura? =

El toggle aparece desactivado y se muestra un aviso. Ajusta los permisos del archivo (`chmod 644` o `chmod 664`) antes de usar el plugin.

= ¿Se pueden perder datos al desactivar Multisite? =

Los datos de los subsitios (tablas `wp_X_posts`, `wp_X_options`, etc.) permanecen en la base de datos. El plugin solo elimina las constantes de configuración de `wp-config.php` y restaura las reglas de `.htaccess`. Para eliminar los datos de la red, hazlo manualmente desde phpMyAdmin.

= ¿Es compatible con Nginx? =

El plugin escribe reglas en `.htaccess`, que Nginx no lee. Si usas Nginx, activa el toggle para que el plugin escriba `wp-config.php` y las tablas, pero añade manualmente las reglas de reescritura Multisite a la configuración de Nginx.

= ¿Qué ocurre si el archivo `.htaccess` no es escribible? =

El plugin escribe `wp-config.php` e instala las tablas igualmente. El aviso de estado indica que `.htaccess` no es escribible para que lo actualices a mano.

== Screenshots ==

1. Página principal con el toggle de Multisite y el panel de estado del sistema.

== Changelog ==

= 1.0.0 =
* Versión inicial.
* Toggle único para activar/desactivar WordPress Multisite.
* Escritura automática de constantes en wp-config.php.
* Actualización de reglas de reescritura en .htaccess.
* Creación de tablas de red con install_network() y populate_network().
* Panel de estado del sistema (multisite, wp-config, htaccess, PHP, WP).
* UI dark con Tailwind CSS, estilo coherente con Settinator.

== Upgrade Notice ==

= 1.0.0 =
Primera versión estable.
