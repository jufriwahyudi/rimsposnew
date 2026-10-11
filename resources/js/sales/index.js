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
                    d.from_date = document.getElementById('from_date')?.value || '';
                    d.to_date = document.getElementById('to_date')?.value || '';
                    d.payment_method = document.getElementById('payment_method')?.value || '';
                    d.payment_status = document.getElementById('payment_status')?.value || '';
                    d.status = document.getElementById('status')?.value || '';
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
                { data: 'payment_status', className: 'text-center' },
                { data: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        const btnFilter = document.getElementById('btnFilter');
        if (btnFilter) {
            btnFilter.addEventListener('click', () => {
                this.table.ajax.reload();
            });
        }

        const btnReset = document.getElementById('btnReset');
        if (btnReset) {
            btnReset.addEventListener('click', () => {
                const fromDate = document.getElementById('from_date');
                const toDate = document.getElementById('to_date');
                const paymentMethod = document.getElementById('payment_method');
                const paymentStatus = document.getElementById('payment_status');
                const status = document.getElementById('status');

                if (fromDate) fromDate.value = '';
                if (toDate) toDate.value = '';
                if (paymentMethod) paymentMethod.value = '';
                if (paymentStatus) paymentStatus.value = '';
                if (status) status.value = '';

                this.table.ajax.reload();
            });
        }

        // Auto reload on filter dropdown change
        ['payment_method', 'payment_status', 'status'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', () => {
                    this.table.ajax.reload();
                });
            }
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
