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

/* Implementation of the Bron Kerbosch algorithm to find all
 * maximal cliques in a graph */
 class CliqueFinder {
     private $graph;
     private $cliques;

     function __construct($g) {
         $this->graph = $g;
         $this->cliques = array();
     }

     /* Returns the cliques found by the Bron Kerbosch algorithm */
     public function get_cliques() {
         return $this->cliques;
     }

    /* Prints the result of the clique clustering */
     public function print_cliques($output = true) {
         $out = "Found ".count($this->cliques)." cliques:<br><br>";
         if($output) $out = '';
         $count = 0;
         foreach($this->cliques as $clique_id => $clique) {
            foreach($clique as $tag_num => $tag) {
                $out .= $tag." ";
            }
            $count += count($clique);
            $out .= ($output)?"\n":"<br>";
         }
         if(!$output) $out .= "<br>Average number of members: ".$count/count($this->cliques);
         echo $out;
     }

     /* Returns the graph on which the algorithm operates */
     public function get_graph() {
         return $this->graph;
     }

     /* Sets the graph on which the algorithm operates */
     public function set_graph($g) {
         $this->graph = $g;
         $this->cliques = array();
     }

    /* Clique finder function (starts recursion) */
     public function find_all_cliques() {
         return $this->find_cliques(array(), $this->graph->nodes, array());
     }

    /* Recursive clique finder function */
     private function find_cliques($clique, $next, $previous) {
         # Bound: Stop, if a node in $previous is related to all nodes in $next
         # (avoids building duplicate subtrees)
         if ($this->node_has_all_neighbours($next, $previous)) {
             return;
         }
         else {
             # Try all expansion candidates
             ////var_dump($next);echo '<br />';//die();
             foreach($next as $node_id => $node_string) {

                 # Remove current node from candidate list
                unset($next[$node_id]);

                 # Add current node to clique
                 $clique[$node_id] = $node_string;

                # New list of candidates for expansion:
                # the nodes that are related to the current node
                 $new_next = array();
                 ////echo "EDGES<br />";
////var_dump($this->graph->edges);echo '<br />';
//             var_dump($next);die('AA');
                 foreach ($next as $n_node_id => $n_node_string) {
                 ////echo $n_node_id."# ".$node_id."#<br />";
////var_dump($this->graph->edges[$n_node_id][$node_id]);
                     if ($this->graph->edges[$n_node_id][$node_id]) {
                     //die('YYY');
                         $new_next[$n_node_id] = $n_node_string;
                     }
                 }

                # New list of known expansions:
                # the nodes that are related to the current node
                 $new_previous = array();
                 foreach ($previous as $p_node_id => $p_node_string) {
                     if ($this->graph->edges[$p_node_id][$node_id]) {
                         $new_previous[$p_node_id] = $p_node_string;
                     }
                 }

                 # If there are no candidates for expansion left and the clique is
                 # maximal, add clique to found cliques
                 if ($new_next == null and $new_previous == null and count($clique) > 1) {
                    $this->cliques[] = $clique;
                 }

                 # Search the new expansions recursively
                 else {
                     $this->find_cliques($clique, $new_next, $new_previous);
                 }

                 # Remove current node from clique
                 unset($clique[$node_id]);

                 # Add current node to known expansions
                 $previous[$node_id] = $node_string;
             }
         }
         return;

     }

    /* Returns true, if there is a node in $p that is neighbour
     * to all nodes in $n */
     private function node_has_all_neighbours($n, $p) {
         $all_neighbours = false;
         foreach($p as $p_node_id => $p_node_string) {
             $all_neighbours = true;
             foreach ($n as $n_node_id => $n_node_string) {
                 if ($this->graph->edges[$n_node_id][$p_node_id] == 0) {
                     $all_neighbours = false;
                 }
             }
         }
         return $all_neighbours;
     }
 }
?>
