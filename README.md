PHP Native Apache Kafka Client 
-----------------

`alpari/kafka-client` is a PHP library implementation of the Apache Kafka protocol, containing both Producer and Consumer support. It was designed to be as close to PHP as possible, keeping API and config as close to the original ones as possible.


Installation
------------

`alpari/kafka-client` can be installed with composer. Installation is quite easy, just ask the Composer to download the library with its dependencies by running the command:

``` bash
$ composer require alpari/kafka-client
```

This library contains several branches, each branch contains support for specfic version of Apache Kafka, see mapping below:

 - Branch 0.8.x is suited for Kafka 0.8.0 versions
 - Branch 0.9.x is suited for Kafka 0.9.0 versions
 - Branch 0.10.x is suited for Kafka 0.10.0 versions
 - Branch master is suited for Kafka 0.11.0 versions
 
Producer API
------------
The Producer API allows applications to send streams of data to topics in the Kafka cluster.

Example showing how to use the producer is given below:

```php
use Alpari\Kafka\DTO\Message;
use Alpari\Kafka\Producer\Config;
use Alpari\Kafka\Producer\KafkaProducer;

include __DIR__ . '/vendor/autoload.php';

$producer = new KafkaProducer([
    Config::BOOTSTRAP_SERVERS => ['tcp://localhost'],
]);
$result = $producer->send('test', Message::fromValue('foo'));
``` 

Only required option is `Config::BOOTSTRAP_SERVERS` which should describe list of Kafka servers used to bootstrap connections to Kafka.

For additional options, please see `Alpari\Kafka\Producer\Config` constants description and [producer configuration]. 


Consumer API
------------

The Consumer API allows applications to read streams of data from topics in the Kafka cluster.

Example showing how to use the consumer is given below.

```php
use Alpari\Kafka;
use Alpari\Kafka\Consumer\Config;
use Alpari\Kafka\Consumer\KafkaConsumer;

$consumer = new KafkaConsumer([
    Config::BOOTSTRAP_SERVERS       => ['tcp://localhost'],
    Config::GROUP_ID                => 'Kafka-Daemon',
    Config::FETCH_MAX_WAIT_MS       => 5000,
    Config::AUTO_OFFSET_RESET       => Kafka\Consumer\OffsetResetStrategy::LATEST,
    Config::SESSION_TIMEOUT_MS      => 30000,
    Config::AUTO_COMMIT_INTERVAL_MS => 10000,
    Config::METADATA_CACHE_FILE     => '/tmp/metadata.php',
]);
$consumer->subscribe(['test']);
for ($i=0; $i<100; $i++) {
    $data = $consumer->poll(1000);
    echo json_encode($data), PHP_EOL;
}
```

For detailed description of configuration, please visit [consumer configuration].


PHP-specific configuration
--------------------------
This library introduces some specific configuration options in order to work faster with PHP:

 - `metadata.cache.file` File name that stores the metadata, this file will be effectively cached by the Opcode cache in production
 - `stream.async.connect` Should client use asynchronous connection to the broker
 - `stream.persistent.connection` Should client use persistent connection to the cluster or not.

For publishing events from web-requests it is recommended to enable persistent connection and configuring path for metadata cache. In this case publishing of event will be as fast as possible.

[producer configuration]: https://kafka.apache.org/documentation/#producerconfigs
[consumer configuration]: https://kafka.apache.org/documentation/#newconsumerconfigs
