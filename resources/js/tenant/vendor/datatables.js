export default async function loadDataTables() {
    const { default: $ } = await import('./jquery.js');
    const mod = await import('datatables.net-dt');
    await import('datatables.net-responsive-dt');
    await import('datatables.net-dt/css/dataTables.dataTables.min.css');
    await import('datatables.net-responsive-dt/css/responsive.dataTables.min.css');
    return mod.default ?? $.fn.dataTable;
}
