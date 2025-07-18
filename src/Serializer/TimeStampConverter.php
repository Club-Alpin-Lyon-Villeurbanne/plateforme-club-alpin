<?php

namespace App\Serializer;

use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use GraphQL\Type\Definition\Type as GraphQLType;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

final class TimeStampConverter implements TypeConverterInterface
{

    public function __construct(private readonly TypeConverterInterface $defaultTypeConverter) {}

    public function convertType(Type $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth): GraphQLType|string|null
    {
        if (isset($property) && property_exists($rootResource, $property)) {
            $reflection = new ReflectionProperty($rootResource, $property);
            if (
                isset($reflection->getAttributes(TimeStamp::class)[0])
            ) {
                return GraphQLType::string();
            }
        }

        return $this->defaultTypeConverter->convertType($type, $input, $rootOperation, $resourceClass, $rootResource, $property, $depth);
    }




    public function resolveType(string $type): ?GraphQLType
    {
        return $this->defaultTypeConverter->resolveType($type);
    }
}
