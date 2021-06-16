<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;

class CashRoundingConfigFieldSerializer extends JsonFieldSerializer
{
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if ($data->getValue() !== null) {
            $value = $data->getValue();
            unset($value['extensions']);

            $data = new KeyValuePair($data->getKey(), $value, $data->isRaw());
        }

        yield from parent::encode($field, $existence, $data, $parameters);
    }

    /**
     * @param string|null $value
     *
     * @return CashRoundingConfig|null
     */
    public function decode(Field $field, $value)
    {
        if ($value === null) {
            return null;
        }

        $raw = json_decode($value, true);

        return new CashRoundingConfig(
            (int) $raw['decimals'],
            (float) $raw['interval'],
            (bool) $raw['roundForNet']
        );
    }
}
