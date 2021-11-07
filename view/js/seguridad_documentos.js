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

$(document).ready(function ()
{
    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    };

    //Obtenemos la información de la página
    var page = getUrlParameter('page');
    //Obtenemos el nick del usuario solicitante
    var usuario = $("li.user > a > span").text();

    switch (page) {
        case 'ventas_factura':
            // $("select[name=forma_pago] option:not(:selected)").attr('disabled', true);
            $("input[name=fecha]").attr('readonly', true);
            $("input[name=hora]").attr('readonly', true);
            break;
        case 'compras_factura':
            // $("select[name=forma_pago] option:not(:selected)").attr('disabled', true);
            $("input[name=fecha]").attr('readonly', true);
            $("input[name=hora]").attr('readonly', true);
            break;
        default:
            break;
    }
});