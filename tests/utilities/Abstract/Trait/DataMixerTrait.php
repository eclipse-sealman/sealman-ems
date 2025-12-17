<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace Tests\Utilities\Abstract\Trait;

/**
 * Code separated for readability.
 *
 * TODO This whole trait may be replaced by MatrixBuilder
 */
trait DataMixerTrait
{
    /**
     * Example for permutations of ['a', 'b', 'c'] would be:
     * abc
     * bac
     * bca
     * acb
     * cab
     * cba.
     */
    public static function getPermutations(array $elements)
    {
        if (count($elements) <= 1) {
            yield $elements;
        } else {
            foreach (static::getPermutations(array_slice($elements, 1)) as $permutation) {
                foreach (range(0, count($elements) - 1) as $i) {
                    yield array_merge(
                        array_slice($permutation, 0, $i),
                        [$elements[0]],
                        array_slice($permutation, $i)
                    );
                }
            }
        }
    }

    /**
     * Example for combinations of [['a', 'b'], ['c', 'd']] would be:
     * ac
     * ad
     * bc
     * bd.
     */
    public static function getArrayCombinations(array $arrays)
    {
        if ([] === $arrays) {
            yield [];

            return;
        }

        $head = array_shift($arrays);

        foreach ($head as $elem) {
            foreach (self::getArrayCombinations($arrays) as $combination) {
                yield [$elem, ...$combination];
            }
        }
    }

    /**
     * Example for combinations of ['a', 'b'] would be:
     * aa
     * ab
     * ba
     * bb.
     *
     * TODO Convert to generator/yield when times allow
     */
    public static function getCombinations($elements)
    {
        $combinations = [];
        $length = count($elements);

        static::combine([], $elements, $length, $combinations);

        return $combinations;
    }

    /**
     * Used by getCombinations.
     */
    private static function combine($prefix, $elements, $length, &$combinations)
    {
        if (count($prefix) === $length) {
            $combinations[] = $prefix;

            return;
        }

        foreach ($elements as $element) {
            static::combine(array_merge($prefix, [$element]), $elements, $length, $combinations);
        }
    }

    /**
     * Example for variations of ['a', 'b', 'c'] would be:
     * a
     * b
     * ab
     * c
     * bc
     * abc.
     */
    public static function getVariations(array $elements)
    {
        $total = (1 << count($elements)) - 1;

        for ($i = 1; $i <= $total; ++$i) {
            // Code left for reference in case such parametrization might be needed
            // if(($i & ($i - 1)) == 0) continue; // avoiding single items since you don't wish to have them
            $variation = [];
            for ($j = 0; $j < count($elements); ++$j) {
                if (($i & (1 << $j)) > 0) {
                    $variation[] = $elements[$j];
                }
            }

            yield $variation;
        }
    }
}
