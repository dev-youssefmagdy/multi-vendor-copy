import '@tenant-css/pages/support.css';

// Support tickets list — all behaviour is declarative via x-tenant::datatable.
// Unread tickets are highlighted by the server's `DT_RowClass` (see
// TicketController::data()), styled by the `.row-unread` rule in support.css.
