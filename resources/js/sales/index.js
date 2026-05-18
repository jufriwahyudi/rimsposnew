import $ from 'jquery';
import 'datatables.net-bs5';

const Sales = {
    table: null,
    init() {
        const tableEl = document.getElementById('salesTable');
        if (!tableEl) return;

        this.table = $('#salesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/sales/datatables',
                data: d => {
                    // You can add additional parameters here if needed
                    d.from_date = document.getElementById('from_date').value;
                    d.to_date = document.getElementById('to_date').value;
                }
            },

            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'sale_date' },
                {
                    data: 'invoice_number',
                    render: (data, type, row) => {
                        return row.invoice_number + '<br><small class="text-muted">' + (row.customer_name ?? 'Walk-in') + '</small>';
                    },
                },
                { data: 'kasir' },
                { data: 'grand_total', className: 'text-end' },
                { data: 'payment_method', className: 'text-center' },
                { data: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });
        document.getElementById('btnFilter').addEventListener('click', () => {
            this.table.ajax.reload();
        });
    },

    showDetail(id) {
        location.href = `/sales/${id}`;
        // next step: modal detail
    }
};

window.Sales = Sales;

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#salesTable')) {
        Sales.init();
    }
});

export default Sales;
