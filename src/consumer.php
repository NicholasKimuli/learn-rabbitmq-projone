<?php

include( dirname( __DIR__ ) . '/vendor/autoload.php' );

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
$consumerTag = 'local.mac.consumer';

$connection = new AMQPStreamConnection( $host, $port, $user, $pass, $vhost );
$channel = $connection->channel();

$channel->queue_declare( $queue, false, true, false, false );
$channel->exchange_declare( $exchange, AMQPExchangeType::DIRECT, false, true, false );
$channel->queue_bind( $queue, $exchange );

/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message( AMQPMessage $message ) {
    $messageBody = json_decode( $message->body );
    $email = $messageBody->email;

    file_put_contents( dirname( __DIR__ ) . '/data/' . $email . '.json', $message->body );

    $message->delivery_info[ 'channel' ]->basic_ack( $message->delivery_info[ 'delivery_tag' ] );
}

$channel->basic_consume( $queue, $consumerTag, false, false, false, false, 'process_message' );

/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown( $channel, $connection ) {
    $channel->close();
    $connection->close();
}

register_shutdown_function( 'shutdown', $channel, $connection );

// Loop as long as the channel has callbacks registered
while ( $channel ->is_consuming() ) {
    $channel->wait();
}
