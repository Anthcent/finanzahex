# Fi-Hex en Dokploy / Hexper Ops

## Configuración

1. Conecta GitHub mediante la invitación restringida y el proveedor asignado por
   el administrador. Autoriza solamente este repositorio; no compartas tokens ni
   credenciales con herramientas de IA.
2. Selecciona la rama revisada y autorizada, ruta de compilación `/` y `Dockerfile`.
   Un push a la rama configurada puede iniciar un despliegue automáticamente.
3. En Hexper Ops selecciona **PostgreSQL automático**. La plataforma debe crear
   una base privada e inyectar `DATABASE_URL` en ejecución, nunca en compilación.
   En Dokploy directo, crea PostgreSQL, conecta ambos servicios a la misma red
   privada y configura esa variable desde el administrador autorizado.
4. Configura `APP_BASE_URL` con el dominio público HTTPS y conserva el puerto
   interno **8080**. La base de datos no necesita un puerto público.
5. Despliega. El registro debe indicar `Database ready; all application migrations applied.`
   antes del inicio de Apache. Comprueba que el contenedor quede `healthy`.

Cambiar el código no crea por sí solo un servicio PostgreSQL en Dokploy: es
necesario seleccionar la opción automática o asociar una base existente.

## Variables y persistencia

| Variable | Uso |
| --- | --- |
| `DATABASE_URL` | Conexión privada PostgreSQL, obligatoria si no se usa la configuración individual. Se admiten esquemas `postgres` y `postgresql`, credenciales codificadas y parámetros TLS como `sslmode`. |
| `APP_BASE_URL` | URL pública de la aplicación, recomendada detrás del proxy. |
| `CI_ENVIRONMENT` | La imagen establece `production`. |
| `DB_DRIVER`, `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` | Alternativa a `DATABASE_URL`; esta última tiene prioridad. El controlador predeterminado es `Postgre`. Para una instalación MySQL existente debe indicarse `MySQLi`. |

Configura los valores sensibles únicamente en el administrador de secretos del
entorno de ejecución. No los añadas a Git, ejemplos, argumentos de compilación,
imágenes ni registros. No se requiere `PORT`.

La imagen ejecuta Apache como `www-data` (UID 33), con `DocumentRoot` en `public/`,
`Listen 0.0.0.0:8080` y una única declaración `EXPOSE 8080`. El healthcheck hace
GET a `http://127.0.0.1:8080/`: una base caída provoca un fallo real de disponibilidad.
La ruta `/health` se conserva como comprobación de proceso, pero no se usa para
declarar disponible la aplicación.

Usa almacenamiento persistente y copias de seguridad para PostgreSQL. Si montas
`/var/www/html/writable` para conservar sesiones, caché o archivos, el volumen debe
ser escribible por UID 33; un bind mount debe prepararlo el administrador.

## Migraciones y compatibilidad

El entrypoint ejecuta `php spark app:prepare` en cada inicio. Espera la conexión,
obtiene un bloqueo de base de datos para evitar migraciones simultáneas y aplica
las pendientes. En PostgreSQL, el esquema y el historial de migraciones se
actualizan en una transacción. Cualquier fallo impide iniciar Apache y devuelve
un código distinto de cero. No se utiliza `writable/installed.lock` ni se migra
desde la primera petición HTTP.

Las migraciones incluyen cuentas, categorías, transacciones y detalles, ventas y
abonos, inventario, estados, conversaciones, auditoría, configuración, clientes,
productos y órdenes de impresión. Se conservan los datos iniciales del catálogo.
Las consultas de reportes, configuración y descuento de stock funcionan en
PostgreSQL; se conserva la compatibilidad de conexión con MySQL.

Una base PostgreSQL nueva inicia vacía, con los datos iniciales. **No se copian
automáticamente los registros de una base MySQL existente.** Si ya hay datos,
mantén la base original, realiza una copia verificada y planifica su importación
validando identificadores, relaciones, saldos, totales y secuencias antes de
cambiar producción. No ejecutes los SQL manuales de MySQL sobre PostgreSQL.

## Compilación y pruebas

Con `DATABASE_URL` ya proporcionada de forma segura al entorno de la terminal:

```sh
docker build -t fihex:local .
docker run -d --name fihex --network RED_PRIVADA -p 8080:8080 -e DATABASE_URL -e APP_BASE_URL fihex:local
docker inspect --format '{{.State.Health.Status}}' fihex
curl --fail http://127.0.0.1:8080/
```

`RED_PRIVADA` representa la red que comunica la aplicación y la base. El comando
principal `apache2-foreground` permanece activo y recibe las señales mediante `exec`.

Prueba completa y aislada en un equipo Linux con Docker, Python 3 y OpenSSL:

```sh
bash tests/docker_smoke.sh
```

El script genera credenciales efímeras en memoria, construye la imagen, crea
PostgreSQL privado, comprueba el healthcheck, UID sin privilegios, operaciones de
los módulos y conservación de registros tras reiniciar. También verifica que un
contenedor sin base de datos termine con error. Los servicios de prueba se
eliminan al terminar. El workflow `PostgreSQL deployment` ejecuta esta comprobación
en las solicitudes de cambio; también puede ejecutarse manualmente.

Para las pruebas existentes, con PHP 8.2 y las extensiones requeridas:

```sh
composer install
vendor/bin/phpunit --no-coverage
```

`tests/deployment_smoke.py --allow-writes` crea datos: úsalo únicamente contra un
despliegue aislado de prueba. Las pruebas cubren almacenamiento de conversaciones;
no realizan llamadas de pago a servicios externos de IA.
