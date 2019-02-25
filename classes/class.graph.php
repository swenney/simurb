<?php
/*
 * Created on 20.12.2009 by Katharina Wäschle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/* Represents a graph */
 class Graph {
     public $nodes = array();
     public $edges = array();

     function __construct($g_nodes, $g_edges) {
         $this->nodes = $g_nodes;
         $this->edges = $g_edges;
     }

    /* Returns the nodes of the graph */
     public function get_nodes() {
         return $this->nodes;
     }
    
    /* Returns the edges of the graph */
     public function get_edges() {
         return $this->edges;
     }
 }
?>
