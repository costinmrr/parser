<?php

namespace Costinmrr\Parser\Xml;

use Costinmrr\Parser\ParserInterface;
use Costinmrr\Parser\ReturnType;
use Costinmrr\Parser\Json\Parser as JsonParser;

class Parser implements ParserInterface
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(protected array $mapping, protected string $content)
    {
    }

    /**
     * @throws \Exception
     */
    public function parse(ReturnType $returnType = ReturnType::DATASET): array
    {
        $xml = simplexml_load_string($this->content);
        if ($xml === false) {
            throw new \Exception('Could not parse the xml content.');
        }
        $jsonObject = new \stdClass();
        static::xmlToObject($xml, $jsonObject);
        $jsonContent = json_encode($jsonObject, JSON_THROW_ON_ERROR);
        return (new JsonParser($this->mapping, $jsonContent))->parse($returnType);
    }

    public function validate(): void
    {
        // TODO: Implement validate() method.
    }

    protected static function xmlToObject(\SimpleXMLElement $xml, \stdClass $parentObject): void
    {
        $currentNode = new \stdClass();

        // Node attributes
        if ($xml->attributes() !== null) {
            foreach ($xml->attributes() as $key => $attribute) {
                $currentNode->{'@' . $key} = (string)$attribute;
            }
        }

        // Node with value
        if (trim((string) $xml) !== '') {
            $currentNode->{'&text'} = trim((string) $xml);
        }

        foreach ($xml->children() as $child) {
            static::xmlToObject($child, $currentNode);
        }

        if (! property_exists($parentObject, $xml->getName())) {
            // Create the node as a property of the object
            $parentObject->{$xml->getName()} = $currentNode;
        } else {
            // The node should be added as an item to an array with the same name
            if (is_object($parentObject->{$xml->getName()})) {
                // Convert to array and set the original object as the first item in the array
                $parentObject->{$xml->getName()} = [$parentObject->{$xml->getName()}];
            }
            // Add the current node to the array
            $parentObject->{$xml->getName()}[] = $currentNode;
        }
    }
}
