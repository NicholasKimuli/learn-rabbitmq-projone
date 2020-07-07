<?php
require( dirname( __DIR__  ) . '/vendor/autoload.php' );

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

$host = 'rattlesnake.rmq.cloudamqp.com';
$user = 'znusfoyt';
$port = 5672;
$pass = 'o7IKrR2QhrcULOBrM_4lAoXtKvVXKu2x';
$vhost = 'znusfoyt';

$exchange = 'subscribers';
$queue = 'projone_subscribers';

$connection = new AMQPStreamConnection( $host, $port, $user, $pass, $vhost );
$channel = $connection->channel();

/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/

/*
    name: $queue
    passive: false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue, false, true, false, false);

/*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/

$channel->exchange_declare( $exchange, AMQPExchangeType::DIRECT, false, true, false );

// Binding queue to exchange
$channel->queue_bind( $queue, $exchange );

// For test data
$faker = Faker\Factory::create();

$a = 0;

while ( $a < 1000 ) {
    $messageBody = json_encode(
        [
            'email' => $faker->email,
            'name' => $faker->name(),
            'address' => $faker->address,
            'subscribed' => true 
        ]
    );
    
    $message = new AMQPMessage( $messageBody,
        [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]
    );

    $channel->basic_publish( $message, $exchange );

    $a++;
}

echo 'Finished publishing to queue: ' . $queue . PHP_EOL;

$channel->close();
$connection->close();
