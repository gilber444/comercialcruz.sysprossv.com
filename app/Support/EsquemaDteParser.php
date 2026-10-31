<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\KeywordValidator;
use Opis\JsonSchema\Keywords\MultipleOfKeyword;
use Opis\JsonSchema\Parsers\Keywords\MultipleOfKeywordParser;
use Opis\JsonSchema\Parsers\SchemaParser;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;

/** Conserva las reglas del schema y evalua multipleOf con los decimales del JSON. */
class EsquemaDteParser extends SchemaParser
{
    protected function parseSchemaKeywords(
        SchemaInfo $info,
        ?KeywordValidator $keywordValidator,
        array $parsers,
        object $shared,
        bool $hasRef = false
    ): Schema {
        foreach ($parsers as $indice => $parser) {
            if (!$parser instanceof MultipleOfKeywordParser) {
                continue;
            }

            $parsers[$indice] = new class('multipleOf') extends MultipleOfKeywordParser {
                public function parse(SchemaInfo $info, SchemaParser $parser, object $shared): ?Keyword
                {
                    // El parser original comprueba que el divisor sea valido.
                    $keyword = parent::parse($info, $parser, $shared);
                    if (!$keyword instanceof MultipleOfKeyword) {
                        return $keyword;
                    }

                    return new class($info->data()->multipleOf) extends MultipleOfKeyword {
                        public function validate(ValidationContext $context, Schema $schema): ?ValidationError
                        {
                            // number_format(..., 14) introduce residuos binarios incluso
                            // en importes validos como 398.23. No redondear ni relajar
                            // el schema: comprobar exactamente el numero serializado.
                            $numero = BigDecimal::of(json_encode($context->currentData(), JSON_THROW_ON_ERROR));
                            $divisor = BigDecimal::of(json_encode($this->number, JSON_THROW_ON_ERROR));

                            if ($numero->remainder($divisor)->isZero()) {
                                return null;
                            }

                            return $this->error($schema, $context, 'multipleOf', 'Number must be a multiple of {divisor}', [
                                'divisor' => $this->number,
                            ]);
                        }
                    };
                }
            };
        }

        return parent::parseSchemaKeywords($info, $keywordValidator, $parsers, $shared, $hasRef);
    }
}
