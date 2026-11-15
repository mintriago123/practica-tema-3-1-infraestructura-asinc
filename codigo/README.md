# Módulo productor y consumidor

Estos archivos se copian a una copia temporal del backend con `scripts/preparar-entorno.sh`. El script no cambia `NexoCommerce/backend`; solo añade `php-amqplib/php-amqplib` y las rutas al directorio `runtime/backend`, que está ignorado.
