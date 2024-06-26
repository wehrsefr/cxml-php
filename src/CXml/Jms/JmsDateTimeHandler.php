<?php

namespace CXml\Jms;

use CXml\Model\Date;
use JMS\Serializer\Context;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;

/**
 * We need a custom DateTime Handler to allow multiple different DateTime versions.
 *
 * Although the cXML documentation defines ISO-8601 as the primary date format, there are cXML implementations
 * which uses milliseconds or a simple date-only format. (i.e. https://www.jaggaer.com/)
 */
class JmsDateTimeHandler
{
    public function serialize(XmlSerializationVisitor $visitor, \DateTimeInterface $date, array $type, Context $context): \DOMText
    {
        if ($date instanceof Date) {
            $format = 'Y-m-d';
        } else {
            $format = $this->getFormat($type);
        }

        return $visitor->visitSimpleString($date->format($format), $type);
    }

    private function getFormat(array $type): string
    {
        return $type['params'][0] ?? \DateTimeInterface::ATOM;
    }

    public function deserialize(XmlDeserializationVisitor $visitor, $dateAsString, array $type, Context $context)
    {
        // explicit date-format was defined in property annotation
        if (isset($type['params'][0])) {
            return \DateTime::createFromFormat($type['params'][0], $dateAsString);
        }

        // else try ISO-8601
        $dateTime = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $dateAsString);
        if ($dateTime) {
            return $dateTime;
        }

        // else try milliseconds-format
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s.vP', $dateAsString);
        if ($dateTime) {
            return $dateTime;
        }

        // else try simple date-format
        $dateTime = Date::createFromFormat('Y-m-d', $dateAsString);
        if ($dateTime) {
            return $dateTime;
        }

        // last resort: throw exception
        throw new \RuntimeException('Could not parse date: '.$dateAsString);
    }
}
