/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
function cargarTipoEdificaciones(){
    var listado = '';
    $.ajax({
        type: 'GET',
        url : url_tipo_edificaciones,
        data : 'busqueda=arbol_tipo_edificaciones',
        async: false,
        success : function(response) {
            if(response.length !== 0){
                listado = response;
            }else{
               alert('¡No hay una estructura asignada para este padre!');
            }
        },
        error: function(response) {
            alert(response);
        }
    });
    return listado;
}
