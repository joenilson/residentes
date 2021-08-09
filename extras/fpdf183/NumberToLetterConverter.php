<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Clase que implementa un conversor de números a letras.
 * @author AxiaCore S.A.S
 * https://github.com/axiacore/number-to-letter-php
 */
class NumberToLetterConverter
{
    private $UNIDADES = array(
        '',
        'UN ',
        'DOS ',
        'TRES ',
        'CUATRO ',
        'CINCO ',
        'SEIS ',
        'SIETE ',
        'OCHO ',
        'NUEVE ',
        'DIEZ ',
        'ONCE ',
        'DOCE ',
        'TRECE ',
        'CATORCE ',
        'QUINCE ',
        'DIECISEIS ',
        'DIECISIETE ',
        'DIECIOCHO ',
        'DIECINUEVE ',
        'VEINTE '
    );

    private $DECENAS = array(
        'VEINTI',
        'TREINTA ',
        'CUARENTA ',
        'CINCUENTA ',
        'SESENTA ',
        'SETENTA ',
        'OCHENTA ',
        'NOVENTA ',
        'CIEN '
    );

    private $CENTENAS = array(
        'CIENTO ',
        'DOSCIENTOS ',
        'TRESCIENTOS ',
        'CUATROCIENTOS ',
        'QUINIENTOS ',
        'SEISCIENTOS ',
        'SETECIENTOS ',
        'OCHOCIENTOS ',
        'NOVECIENTOS '
    );

    private $MONEDAS = [
        ['country' => 'Colombia', 'currency' => 'COP', 'singular' => 'PESO COLOMBIANO', 'plural' => 'PESOS COLOMBIANOS', 'symbol', '$'],
        ['country' => 'Estados Unidos', 'currency' => 'USD', 'singular' => 'DÓLAR', 'plural' => 'DÓLARES', 'symbol', 'US$'],
        ['country' => 'El Salvador', 'currency' => 'USD', 'singular' => 'DÓLAR', 'plural' => 'DÓLARES', 'symbol', 'US$'],
        ['country' => 'Europa', 'currency' => 'EUR', 'singular' => 'EURO', 'plural' => 'EUROS', 'symbol', '€'],
        ['country' => 'México', 'currency' => 'MXN', 'singular' => 'PESO MEXICANO', 'plural' => 'PESOS MEXICANOS', 'symbol', '$'],
        ['country' => 'Perú', 'currency' => 'PEN', 'singular' => 'NUEVO SOL', 'plural' => 'NUEVOS SOLES', 'symbol', 'S/'],
        ['country' => 'Reino Unido', 'currency' => 'GBP', 'singular' => 'LIBRA', 'plural' => 'LIBRAS', 'symbol', '£'],
        ['country' => 'Argentina', 'currency' => 'ARS', 'singular' => 'PESO', 'plural' => 'PESOS', 'symbol', '$'],
        ['country' => 'República Dominicana', 'currency' => 'DOP', 'singular' => 'PESO DOMINICANO', 'plural' => 'PESOS DOMINICANOS', 'symbol', 'RD$']
    ];

    private $separator = FS_NF2;
    private $decimal_mark = FS_NF1;
    private $glue = ' CON ';

    /**
     * Evalua si el número contiene separadores o decimales
     * formatea y ejecuta la función conversora
     * @param number $number número a convertir
     * @param string $miMoneda clave de la moneda
     * @return string completo
     */
    public function to_word($number, $miMoneda = null)
    {
        if (strpos($number, $this->decimal_mark) === false) {
            $convertedNumber = array(
                $this->convertNumber($number, $miMoneda, 'entero')
            );
        } else {
            $number = explode($this->decimal_mark, str_replace($this->separator, '', trim($number)));

            $convertedNumber = array(
                $this->convertNumber($number[0], $miMoneda, 'entero'),
                $this->convertNumber($number[1], $miMoneda, 'decimal'),
            );
        }
        return implode($this->glue, array_filter($convertedNumber));
    }

    /**
     * Convierte número a letras
     * @param number $number
     * @param string $miMoneda
     * @param string $type tipo de dígito (entero/decimal)
     * @return string $converted string convertido
     */
    private function convertNumber($number, $miMoneda = null, string $type = 'entero')
    {

        $converted = '';
        $moneda = '';
        if ($miMoneda !== null) {
            try {
                $moneda = array_filter($this->MONEDAS, static function ($m) use ($miMoneda) {
                    return ($m['currency'] === $miMoneda);
                });

                $moneda = array_values($moneda);

                if (count($moneda) <= 0) {
                    throw new Exception("Tipo de moneda inválido");
                    //return;
                }
                ($number < 2 ? $moneda = $moneda[0]['singular'] : $moneda = $moneda[0]['plural']);
            } catch (Exception $e) {
                echo $e->getMessage();
                //return;
            }
        }

        if (($number < 0) || ($number > 999999999)) {
            return ($type === 'decimal')?' 00/100' : '';
        }

        $numberStr = (string) $number;
        $numberStrFill = str_pad($numberStr, 9, '0', STR_PAD_LEFT);
        $millones = substr($numberStrFill, 0, 3);
        $miles =  substr($numberStrFill, 3, 3);
        $cientos = substr($numberStrFill, 6);

        if ($millones > 0) {
            if ($millones === '001') {
                $converted .= 'UN MILLON ';
            } elseif ($millones > 0) {
                $converted .= sprintf('%sMILLONES ', $this->convertGroup($millones));
            }
        }

        if ($miles > 0) {
            if ($miles === '001') {
                $converted .= 'MIL ';
            } elseif ($miles > 0) {
                $converted .= sprintf('%sMIL ', $this->convertGroup($miles));
            }
        }

        if ($cientos > 0) {
            if ($cientos === '001') {
                $converted .= 'UN ';
            } elseif ($cientos > 0) {
                $converted .= sprintf('%s ', $this->convertGroup($cientos));
            }
        }

        $converted .= $moneda;

        return $converted;
    }

    /**
     * Define el tipo de representación decimal (centenas/millares/millones)
     * @param string $n
     * @return string $output
     */
    private function convertGroup($n)
    {
        $output = '';

        if ($n === '100') {
            $output = "CIEN ";
        } elseif ($n[0] !== '0') {
            $output = $this->CENTENAS[(int) $n[0] - 1];
        }

        $k = (int) substr($n, 1);

        if ($k <= 20) {
            $output .= $this->UNIDADES[$k];
        } elseif (($k > 30) && ($n[2] !== '0')) {
            $output .= sprintf('%sY %s', $this->DECENAS[(int)$n[1] - 2], $this->UNIDADES[(int)$n[2]]);
        } else {
            $output .= sprintf('%s%s', $this->DECENAS[(int)$n[1] - 2], $this->UNIDADES[(int)$n[2]]);
        }
        return $output;
    }
}