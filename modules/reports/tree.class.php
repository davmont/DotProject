<?php

define ("TREE_ROOT", 0);
define ("COMPANY", 1);
define ("PROJECT", 2);
define ("TASK", 3);
define ("DEPARTMENT", 4);
define ("EMPLOYEE", 5);
define ("SUMMARY", 6);
define ("BOTTOM_ROW", SUMMARY);

class Tree {
	
	var $node = null;
	var $children = array();
	
	function Tree( &$new_node ) {
		$this->node =& $new_node;
	}
	
	function &add_child( &$node ) {
		$new_guy = new Tree( $node );
			
		return $this->children[] =& $new_guy;
	}
	
	function count_leaves( $depth = -1 ) {
		if ( count($this->children) == 0 or $this->node->level == $depth ) {
			return 1;
		} else {
			$this_call_ttl = 0;
			for ( $i = 0; $i < count($this->children); $i++ ) {
				$this_call_ttl += $this->children[$i]->count_leaves( $depth );		
			}
			return $this_call_ttl;
		}
	}
	
}

class TreeNode {
	
	var $level = 0;
	var $text_content = null;
	var $hours = array();
	
	function TreeNode( $level, $content = "" ){
		$this->level = $level;
		$this->text_content = $content;
	}
	
	function add_hours ( $new_hours ) {
		foreach ( $new_hours as $key => $val ) {
			$this->hours[$key] = (double)@$this->hours[$key] + (double)$val;
		}
	}
}

class TreeLeaf {
	
	var $cost_code = null;
	var $summary = null;
	var $hours = array();
	var $level = 6;

	function TreeLeaf( $cost_code = "", $summary = "", &$hours ) {
		$this->cost_code = $cost_code;
		$this->summary = $summary;
		$this->hours = $hours;
	}
}

function &make_new_tree() {
	
	return new Tree( new TreeNode(0) );
}

function add_task_log( &$tree, &$row, &$hours ) {
	
	$leaf = new TreeLeaf( @$row['task_log_cost_code'], @$row['task_log_summary'], $hours );
	$location_hash = array ( 	COMPANY    => @$row['company_name'],
								PROJECT    => @$row['project_name'],
								TASK       => @$row['task_name'],
								DEPARTMENT => @$row['department_name'],
								EMPLOYEE   => @$row['employee_name']
							);
	
	$node =& get_node( $tree, $location_hash, $hours);
		
	$node->add_child( $leaf );

}

function &get_node( &$tree, &$location_hash, &$hours) {
	
	if ( $tree->node->level == TREE_ROOT ) $tree->node->add_hours( $hours );
	
	if ( $tree->node->level == BOTTOM_ROW - 1  ) {	
		return $tree;
	} else  {
		$node_exists = false;
		if ( is_array ( $tree->children ) ) {
			reset($tree->children);
			for ( $i = 0; $i < count($tree->children); $i++ ) {
				
				$child =& $tree->children[$i];
				
				if ( $child->node->text_content == $location_hash[$child->node->level] ) {
					
					$child->node->add_hours( $hours ) ;
					
					$node_exists = true;
					return get_node( $child, $location_hash, $hours);
				}
			}
		}
		if ( !$node_exists ) {
			$new_node = new TreeNode( 1 + $tree->node->level, $location_hash[1 + $tree->node->level]);
			
			$new_node->add_hours( $hours );
			
			$added_node =& $tree->add_child( $new_node );
			
			return get_node( $added_node, $location_hash, $hours);
		}
	}
}


function print_tree(&$tree, $columns = null) {
	
	if ( !$columns ) {
		$columns = array( COMPANY, PROJECT, TASK, DEPARTMENT, EMPLOYEE, SUMMARY);
	}
	
	//Print table headers
	print "<TABLE border=0 class=\"tbl\" height=\"100%\">\n";
	print "\t<TR>\n";
	if ( in_array( COMPANY, $columns ) )
		print "\t<TH>Company</TH>\n";
	if ( in_array( PROJECT, $columns ) )
		print "\t<TH>Project</TH>\n";
	if ( in_array( TASK, $columns ) )
		print "\t<TH>Task</TH>\n";
	if ( in_array( DEPARTMENT, $columns ) )
		print "\t<TH>Department</TH>\n";
	if ( in_array( EMPLOYEE, $columns ) )
		print "\t<TH>Employee</TH>\n";
	if ( in_array( SUMMARY, $columns ) ) {
		print "\t<TH>Work Category</TH>\n";
		print "\t<TH>Summary</TH>\n";
	}

	//print headers for all the hour types
	foreach ( $tree->node->hours as $key => $val ) {
		print "\t\t<TH>$key</TH>\n";
	}

	//end header row and begin main part
	print "\t</TR>\n\t<TR>\n";

	//recursively print main table
	print_tree_rcr( $tree, $columns );
	
	//colspan to line up total hours in the total row
	$bottom_colspan = count( $columns );
	
	if ( !in_array(SUMMARY, $columns) ) $bottom_colspan--;
	
	print "\t\t<TD STYLE=\"font-size: 130%\">Category Total:</TD>\n";
	
	if ( $bottom_colspan ) 
		print "\t\t<TD COLSPAN=$bottom_colspan>&nbsp;</TD>\n";
	
	$grand_total = 0;
	foreach ( $tree->node->hours as $val ) {
		print "\t\t<TD STYLE=\"font-size: 130%; text-align: right;\">" . sprintf( "%.2f", $val ) . "</TD>\n";
		$grand_total += $val;
	}
	
	print "\t</TR>\n\t<TR>\n\t\t<TD STYLE=\"font-size: 130%\"><b>Total:<b></TD>\n";
	
	if ( $bottom_colspan )
		print "\t\t<TD COLSPAN=$bottom_colspan>&nbsp;</TD>\n";
	
	print "\t\t<TD COLSPAN=" . count($tree->node->hours) . " STYLE=\"font-size: 130%; text-align: right;\"><b>" . sprintf( "%.2f", $grand_total ) . "<b></TD>\n";
	
	print "\t</TR>\n</TABLE>";
	
	
}

function print_tree_rcr( &$tree, &$columns ) {

	for ($i = $tree->node->level; $i <= BOTTOM_ROW; $i++) {
		if ( in_array( $i, $columns ) )
			$lowest_hierarchy = $i;
	}

	if ( $tree->node->level !== 0 && in_array( $tree->node->level, $columns ) ) {
		if ( $tree->node->level != $lowest_hierarchy ) {

			print "\t\t<TD STYLE=\"vertical-align: top;\" rowspan=" . $tree->count_leaves( $lowest_hierarchy );
			print "><b>{$tree->node->text_content}</b>\n";
			print "\t\t\t<DIV STYLE=\"position: relative; top 50%; text-align: left; vertical-align: middle;\">\n";
			//print "\t\t\t<DIV STYLE=\"text-align: left; /*height: 75%; border: 1px solid blue;*/ \">\n";
			//print "\t\t\t<SPAN STYLE=\"vertical-align: text-middle;\">\n";
			//echo "\t\t\t<table height=\"100%\" border=1><tr><td>";
			foreach ( $tree->node->hours as $type => $hr) {
				print "\t\t\t\t<br>" . sprintf( "%.2f", $hr ) . "&nbsp;$type\n";
			}
			//print "</SPAN>";
			print "\t\t\t</DIV>\n";
			//echo "\t\t\t</td></tr></table>";
			print "\t\t</TD>\n";
		} else {
			if ( get_class( $tree->node ) == "treenode" ) {
				print "\t\t<TD valign=top >{$tree->node->text_content}\n\t\t</TD>\n";
			} else {
				print "\t\t<TD valign=top >{$tree->node->cost_code}</TD>\n\t\t<TD>{$tree->node->summary}</TD>\n";
			}
			foreach ( $tree->node->hours as $val ) {
				print "\t\t<TD align=\"right\">". sprintf( "%.2f", $val ) . "</TD>\n";
			}
			print "\t</TR>\n\t<TR>\n";
		}
	}
	
	if ( $tree->node->level != $lowest_hierarchy ) {
		reset( $tree->children );
		while ( list(, $child) = each ( $tree->children ) ) {
			print_tree_rcr( $child, $columns );
		}	
	}
}


function simple_print ( &$tree ) {
	
	print "<TABLE border = 1><TR><TH>Level</TH><TH>Content/Summary</TH><TH>Children</TH></TR>";
	
	simple_print_rcr ( $tree );
	
	print "\t</TR>\n</TABLE>";
	
}
	
function simple_print_rcr( &$tree ) {	
	if ( get_class( $tree->node ) == "treenode" )
		print ("<TR><TD>{$tree->node->level}</TD><TD>{$tree->node->text_content}</TD><TD>". count( $tree->children ) ."</TD></TR>\n");
	else
		print ("<TR><TD colspan=3>{$tree->node->summary}</TD></TR>\n");
	reset( $tree->children );
	while ( list(, $child) = each ( $tree->children ) ) {
		simple_print_rcr( $child );
	}
}
	