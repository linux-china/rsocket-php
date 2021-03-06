<?php


namespace RSocket\routing;

use RSocket\metadata\CompositeMetadata;
use RSocket\metadata\RoutingMetadata;
use RSocket\Payload;
use RSocket\RSocket;
use RSocket\utils\UTF8;
use Rx\Observable;

class RSocketServiceStub
{
    private string $serviceName;
    /**
     * @var Observable<RSocket>
     */
    private Observable $target;

    public function __construct(string $serviceName, Observable $target)
    {
        $this->serviceName = $serviceName;
        $this->target = $target;
    }

    public function __call(string $methodName, array $params): Observable
    {
        $routingKey = $this->serviceName . '.' . $methodName;
        return $this->target->flatMap(function (RSocket $rsocket) use (&$routingKey, &$params) {
            $compositeMetadata = CompositeMetadata::fromEntries(new RoutingMetadata($routingKey));
            $payloadData = null;
            if ($params !== null) {
                $payloadData = UTF8::encode(json_encode($params));
            }
            return $rsocket->requestResponse(Payload::fromArray($compositeMetadata->toUint8Array(), $payloadData))
                ->map(function (Payload $payload) use ($routingKey) {
                    return JsonDecodeFactory::decodeUtf8Text($payload->getDataUtf8(), $routingKey);
                });
        });
    }

}