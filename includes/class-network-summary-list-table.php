<?php

abstract class Network_Summary_List_Table {
	protected $items;

	public function prepare_items() {
		$this->items = $this->get_items();
	}

	protected abstract function get_items();

	public function display() {
		$this->display_tablenav( 'top' );

		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody id="the-list">
			<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<br class="clear"/>
		</div>
	<?php
	}

	private function get_table_classes() {
		return array( 'widefat', 'fixed' );
	}

	private function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $widths ) = $this->get_column_info();

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) ) {
				$style = 'display:none;';
			} else if ( isset( $widths[ $column_key ] ) ) {
				$style = 'width:' . $widths[ $column_key ];
			}

			if ( strlen( $style ) > 0 ) {
				$style = ' style="' . $style . '"';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			printf( '<th scope="col" %s %s %s>%s</th>', $id, $class, $style, $column_display_name );
		}
	}

	private function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	public function has_items() {
		return ! empty( $this->items );
	}

	private function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	private function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr' . $row_class . '>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	private function single_row_columns( $item ) {
		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class='$column_name column-$column_name'";

			$style = '';
			if ( in_array( $column_name, $hidden ) ) {
				$style = ' style="display:none;"';
			}

			$attributes = "$class$style";

			printf( '<td %1$s> %2$s</td>', $attributes, $this->single_row_column_value( $column_name, $item ) );
		}
	}

	private function single_row_column_value( $column_name, $item ) {
		if ( method_exists( $this, 'column_' . $column_name ) ) {
			return call_user_func( array( $this, 'column_' . $column_name ), $item );
		} elseif ( method_exists( $item, 'get' . ucfirst( $column_name ) ) ) {
			return call_user_func( array( $item, 'get' . ucfirst( $column_name ) ), $item );
		} elseif ( is_object( $item ) && property_exists( $item, $column_name ) ) {
			return $item->$column_name;
		} elseif ( is_array( $item ) && isset( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		} else {
			return '';
		}
	}

	private function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden = array_intersect( array_keys( $columns ), array_filter( $hidden ) );

		return count( $columns ) - count( $hidden );
	}

	private function get_column_info() {
		return array(
			$this->get_all_columns(),
			$this->get_hidden_columns(),
			$this->get_column_widths()
		);
	}

	protected abstract function get_all_columns();

	protected function get_hidden_columns() {
		return array();
	}

	protected function get_column_widths() {
		return array();
	}

	public function no_items() {
		_e( 'No items found.' );
	}
}