<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin leads list page with export functionality
 */

/**
 * Register leads submenu
 */
function wafbp_register_leads_menu() {
    add_submenu_page(
        'wafbp-forms',
        __( 'Leads', 'whatsapp-form-builder-pro' ),
        __( 'Leads', 'whatsapp-form-builder-pro' ),
        'manage_options',
        'wafbp-leads',
        'wafbp_leads_page'
    );
}
add_action( 'admin_menu', 'wafbp_register_leads_menu' );

/**
 * Leads list page
 */
function wafbp_leads_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'whatsapp-form-builder-pro' ) );
    }
    
    global $wpdb;
    $leads_table = $wpdb->prefix . 'wafbp_leads';
    $forms_table = $wpdb->prefix . 'wafbp_forms';
    
    // Handle bulk delete
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'delete' && isset( $_POST['lead_ids'] ) && is_array( $_POST['lead_ids'] ) ) {
        if ( ! check_admin_referer( 'wafbp_bulk_delete_leads' ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        } else {
            $lead_ids = array_map( 'absint', $_POST['lead_ids'] );
            $placeholders = implode( ',', array_fill( 0, count( $lead_ids ), '%d' ) );
            $deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $leads_table WHERE id IN ($placeholders)", $lead_ids ) );
            if ( $deleted !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( '%d lead(s) deleted successfully.', 'whatsapp-form-builder-pro' ), $deleted ) . '</p></div>';
            }
        }
    }
    
    // Handle single delete
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['lead_id'] ) ) {
        $lead_id = absint( $_GET['lead_id'] );
        if ( ! check_admin_referer( 'delete_lead_' . $lead_id ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'whatsapp-form-builder-pro' ) . '</p></div>';
        } else {
            $deleted = $wpdb->delete( $leads_table, array( 'id' => $lead_id ), array( '%d' ) );
            if ( $deleted !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Lead deleted successfully.', 'whatsapp-form-builder-pro' ) . '</p></div>';
            }
        }
    }
    
    // Handle CSV export
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! check_admin_referer( 'wafbp_export_csv' ) ) {
            wp_die( __( 'Security check failed.', 'whatsapp-form-builder-pro' ) );
        }
        wafbp_export_leads_csv();
        exit;
    }
    
    // Get filters
    $form_id_filter = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
    $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
    $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
    
    // Pagination
    $per_page = 20;
    $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $offset = ( $current_page - 1 ) * $per_page;
    
    // Build query
    $where = array( '1=1' );
    $where_values = array();
    
    if ( $form_id_filter > 0 ) {
        $where[] = 'form_id = %d';
        $where_values[] = $form_id_filter;
    }
    
    if ( ! empty( $date_from ) ) {
        $where[] = 'DATE(created_at) >= %s';
        $where_values[] = $date_from;
    }
    
    if ( ! empty( $date_to ) ) {
        $where[] = 'DATE(created_at) <= %s';
        $where_values[] = $date_to;
    }
    
    $where_clause = implode( ' AND ', $where );
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM $leads_table WHERE $where_clause";
    if ( ! empty( $where_values ) ) {
        $count_query = $wpdb->prepare( $count_query, $where_values );
    }
    $total_items = $wpdb->get_var( $count_query );
    $total_pages = ceil( $total_items / $per_page );
    
    // Get leads
    $query = "SELECT * FROM $leads_table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $query_values = array_merge( $where_values, array( $per_page, $offset ) );
    $leads = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) );
    
    // Get all forms for filter dropdown
    $forms = $wpdb->get_results( "SELECT id, form_name FROM $forms_table ORDER BY form_name ASC" );
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Leads', 'whatsapp-form-builder-pro' ); ?></h1>
        
        <!-- Filters -->
        <form method="get" action="">
            <input type="hidden" name="page" value="wafbp-leads">
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="form_id">
                        <option value="0"><?php echo esc_html__( 'All Forms', 'whatsapp-form-builder-pro' ); ?></option>
                        <?php foreach ( $forms as $form ) : ?>
                            <option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form_id_filter, $form->id ); ?>>
                                <?php echo esc_html( $form->form_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php echo esc_attr__( 'From', 'whatsapp-form-builder-pro' ); ?>">
                    <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="<?php echo esc_attr__( 'To', 'whatsapp-form-builder-pro' ); ?>">
                    
                    <input type="submit" class="button" value="<?php echo esc_attr__( 'Filter', 'whatsapp-form-builder-pro' ); ?>">
                    
                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'wafbp-leads', 'action' => 'export_csv', 'form_id' => $form_id_filter, 'date_from' => $date_from, 'date_to' => $date_to ), admin_url( 'admin.php' ) ), 'wafbp_export_csv' ) ); ?>" class="button button-primary">
                        <?php echo esc_html__( 'Export CSV', 'whatsapp-form-builder-pro' ); ?>
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Bulk actions form -->
        <form method="post" action="">
            <?php wp_nonce_field( 'wafbp_bulk_delete_leads' ); ?>
            <input type="hidden" name="action" value="delete">
            
            <?php if ( empty( $leads ) ) : ?>
                <p><?php echo esc_html__( 'No leads found.', 'whatsapp-form-builder-pro' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column"><input type="checkbox" id="cb-select-all"></td>
                            <th><?php echo esc_html__( 'Name', 'whatsapp-form-builder-pro' ); ?></th>
                            <th><?php echo esc_html__( 'Phone', 'whatsapp-form-builder-pro' ); ?></th>
                            <th><?php echo esc_html__( 'City', 'whatsapp-form-builder-pro' ); ?></th>
                            <th><?php echo esc_html__( 'Subject', 'whatsapp-form-builder-pro' ); ?></th>
                            <th><?php echo esc_html__( 'Form', 'whatsapp-form-builder-pro' ); ?></th>
                            <th><?php echo esc_html__( 'Date', 'whatsapp-form-builder-pro' ); ?></th>
                            <th><?php echo esc_html__( 'Actions', 'whatsapp-form-builder-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $leads as $lead ) :
                            $form = $wpdb->get_row( $wpdb->prepare( "SELECT form_name FROM $forms_table WHERE id = %d", $lead->form_id ) );
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="lead_ids[]" value="<?php echo esc_attr( $lead->id ); ?>">
                                </th>
                                <td><?php echo esc_html( $lead->name ); ?></td>
                                <td><?php echo esc_html( $lead->phone ); ?></td>
                                <td><?php echo esc_html( $lead->city ); ?></td>
                                <td><?php echo esc_html( $lead->subject ); ?></td>
                                <td><?php echo $form ? esc_html( $form->form_name ) : esc_html__( 'N/A', 'whatsapp-form-builder-pro' ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead->created_at ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wafbp-leads', 'action' => 'view', 'lead_id' => $lead->id ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small">
                                        <?php echo esc_html__( 'View', 'whatsapp-form-builder-pro' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'wafbp-leads', 'action' => 'delete', 'lead_id' => $lead->id ), admin_url( 'admin.php' ) ), 'delete_lead_' . $lead->id ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this lead?', 'whatsapp-form-builder-pro' ) ); ?>');">
                                        <?php echo esc_html__( 'Delete', 'whatsapp-form-builder-pro' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <input type="submit" class="button action" value="<?php echo esc_attr__( 'Delete Selected', 'whatsapp-form-builder-pro' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete selected leads?', 'whatsapp-form-builder-pro' ) ); ?>');">
                    </div>
                    
                    <?php if ( $total_pages > 1 ) : ?>
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo esc_html( sprintf( __( '%s items', 'whatsapp-form-builder-pro' ), number_format_i18n( $total_items ) ) ); ?></span>
                            <?php
                            $page_links = paginate_links( array(
                                'base' => add_query_arg( 'paged', '%#%' ),
                                'format' => '',
                                'prev_text' => __( '&laquo;', 'whatsapp-form-builder-pro' ),
                                'next_text' => __( '&raquo;', 'whatsapp-form-builder-pro' ),
                                'total' => $total_pages,
                                'current' => $current_page,
                            ) );
                            echo $page_links;
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
        
        <!-- Single lead view -->
        <?php if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && isset( $_GET['lead_id'] ) ) :
            $lead_id = absint( $_GET['lead_id'] );
            $lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $leads_table WHERE id = %d", $lead_id ) );
            if ( $lead ) :
                $form = $wpdb->get_row( $wpdb->prepare( "SELECT form_name FROM $forms_table WHERE id = %d", $lead->form_id ) );
                ?>
                <div class="wafbp-lead-detail" style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccc;">
                    <h2><?php echo esc_html__( 'Lead Details', 'whatsapp-form-builder-pro' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php echo esc_html__( 'ID', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( $lead->id ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'Form', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo $form ? esc_html( $form->form_name ) : esc_html__( 'N/A', 'whatsapp-form-builder-pro' ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'Name', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( $lead->name ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'Phone', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( $lead->phone ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'City', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( $lead->city ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'Subject', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( $lead->subject ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'Message', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( $lead->message ); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__( 'Page URL', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><a href="<?php echo esc_url( $lead->page_url ); ?>" target="_blank"><?php echo esc_html( $lead->page_url ); ?></a></td>
                        </tr>
                        <?php if ( $lead->ip_address ) : ?>
                            <tr>
                                <th><?php echo esc_html__( 'IP Address', 'whatsapp-form-builder-pro' ); ?></th>
                                <td><?php echo esc_html( $lead->ip_address ); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ( $lead->user_agent ) : ?>
                            <tr>
                                <th><?php echo esc_html__( 'User Agent', 'whatsapp-form-builder-pro' ); ?></th>
                                <td><?php echo esc_html( $lead->user_agent ); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th><?php echo esc_html__( 'Date', 'whatsapp-form-builder-pro' ); ?></th>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead->created_at ) ) ); ?></td>
                        </tr>
                    </table>
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wafbp-leads' ) ); ?>" class="button">
                            <?php echo esc_html__( '← Back to Leads', 'whatsapp-form-builder-pro' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#cb-select-all').on('click', function() {
            $('input[name="lead_ids[]"]').prop('checked', this.checked);
        });
    });
    </script>
    <?php
}

/**
 * Export leads to CSV
 */
function wafbp_export_leads_csv() {
    global $wpdb;
    $leads_table = $wpdb->prefix . 'wafbp_leads';
    $forms_table = $wpdb->prefix . 'wafbp_forms';
    
    // Get filters
    $form_id_filter = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
    $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
    $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
    
    // Build query
    $where = array( '1=1' );
    $where_values = array();
    
    if ( $form_id_filter > 0 ) {
        $where[] = 'form_id = %d';
        $where_values[] = $form_id_filter;
    }
    
    if ( ! empty( $date_from ) ) {
        $where[] = 'DATE(created_at) >= %s';
        $where_values[] = $date_from;
    }
    
    if ( ! empty( $date_to ) ) {
        $where[] = 'DATE(created_at) <= %s';
        $where_values[] = $date_to;
    }
    
    $where_clause = implode( ' AND ', $where );
    
    // Get leads
    $query = "SELECT * FROM $leads_table WHERE $where_clause ORDER BY created_at DESC";
    if ( ! empty( $where_values ) ) {
        $query = $wpdb->prepare( $query, $where_values );
    }
    $leads = $wpdb->get_results( $query );
    
    // Set headers for CSV download
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=leads-' . date( 'Y-m-d-H-i-s' ) . '.csv' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    
    // Open output stream
    $output = fopen( 'php://output', 'w' );
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );
    
    // CSV headers
    fputcsv( $output, array(
        __( 'ID', 'whatsapp-form-builder-pro' ),
        __( 'Form', 'whatsapp-form-builder-pro' ),
        __( 'Name', 'whatsapp-form-builder-pro' ),
        __( 'Phone', 'whatsapp-form-builder-pro' ),
        __( 'City', 'whatsapp-form-builder-pro' ),
        __( 'Subject', 'whatsapp-form-builder-pro' ),
        __( 'Message', 'whatsapp-form-builder-pro' ),
        __( 'Page URL', 'whatsapp-form-builder-pro' ),
        __( 'IP Address', 'whatsapp-form-builder-pro' ),
        __( 'User Agent', 'whatsapp-form-builder-pro' ),
        __( 'Date', 'whatsapp-form-builder-pro' ),
    ) );
    
    // CSV rows
    foreach ( $leads as $lead ) {
        $form = $wpdb->get_row( $wpdb->prepare( "SELECT form_name FROM $forms_table WHERE id = %d", $lead->form_id ) );
        $form_name = $form ? $form->form_name : __( 'N/A', 'whatsapp-form-builder-pro' );
        
        fputcsv( $output, array(
            $lead->id,
            $form_name,
            $lead->name,
            $lead->phone,
            $lead->city,
            $lead->subject,
            $lead->message,
            $lead->page_url,
            $lead->ip_address,
            $lead->user_agent,
            $lead->created_at,
        ) );
    }
    
    fclose( $output );
}
