// ============================================
// FarmaciaPro - Main JavaScript
// ============================================

$(document).ready(function () {

    // ── Sidebar Toggle ──
    $('#sidebarToggle').on('click', function () {
        $('body').toggleClass('sidebar-collapsed');
        if ($(window).width() <= 768) {
            $('#sidebar').toggleClass('open');
        }
    });

    // Close sidebar on mobile when clicking outside
    $(document).on('click', function (e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                $('#sidebar').removeClass('open');
            }
        }
    });

    // ── DataTables Default ──
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            pageLength: 15,
            dom: "<'row mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>rt<'row mt-3'<'col-sm-6'i><'col-sm-6'p>>",
            responsive: true
        });
    }

    // ── Auto dismiss alerts ──
    setTimeout(function () {
        $('.alert-dismissible').fadeOut(500, function () { $(this).remove(); });
    }, 4000);

    // ── Confirm Delete ──
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        Swal.fire({
            title: 'Tem a certeza?',
            text: 'Esta acção não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#94A3B8',
            confirmButtonText: 'Sim, eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
    });

    // ── Format currency inputs ──
    $(document).on('input', '.currency-input', function () {
        let val = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(val);
    });

    // ── Tooltips ──
    $('[data-bs-toggle="tooltip"]').tooltip();

    // ── Toast notifications ──
    window.showToast = function (msg, type = 'success') {
        const icons = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
        const toast = $(`
            <div class="toast align-items-center text-bg-${type} border-0 show" role="alert" style="position:fixed;bottom:20px;right:20px;z-index:9999;min-width:280px">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-${icons[type]} me-2"></i>${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(400, () => toast.remove()), 4000);
    };
});

// ── POS Cart ──
const Cart = {
    items: [],

    add(product) {
        const existing = this.items.find(i => i.id === product.id);
        if (existing) {
            existing.qty++;
        } else {
            this.items.push({ ...product, qty: 1 });
        }
        this.render();
    },

    remove(id) {
        this.items = this.items.filter(i => i.id !== id);
        this.render();
    },

    updateQty(id, qty) {
        const item = this.items.find(i => i.id === id);
        if (item) {
            item.qty = parseInt(qty) || 1;
            if (item.qty <= 0) this.remove(id);
        }
        this.render();
    },

    getTotal() {
        return this.items.reduce((sum, i) => sum + (i.preco_venda * i.qty), 0);
    },

    render() {
        const container = $('#cart-items');
        if (!container.length) return;

        if (this.items.length === 0) {
            container.html('<p class="text-muted text-center small mt-3">Carrinho vazio</p>');
            $('#cart-total-value').text('Kz 0,00');
            $('#btn-checkout').prop('disabled', true);
            return;
        }

        let html = '';
        this.items.forEach(item => {
            html += `<div class="pos-cart-item">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="fw-semibold" style="font-size:12px">${item.nome}</span>
                    <button class="btn btn-sm text-danger p-0 ms-2" onclick="Cart.remove(${item.id})"><i class="bi bi-x"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" min="1" value="${item.qty}" class="form-control form-control-sm" style="width:60px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff"
                        onchange="Cart.updateQty(${item.id}, this.value)">
                    <span class="ms-auto" style="font-size:12px">Kz ${(item.preco_venda * item.qty).toLocaleString('pt-AO', {minimumFractionDigits:2})}</span>
                </div>
            </div>`;
        });
        container.html(html);

        const total = this.getTotal();
        $('#cart-total-value').text('Kz ' + total.toLocaleString('pt-AO', { minimumFractionDigits: 2 }));
        $('#btn-checkout').prop('disabled', false);

        // Populate hidden inputs for form submission
        $('#cart-data').val(JSON.stringify(this.items));
        $('#cart-total').val(total.toFixed(2));
    },

    clear() {
        this.items = [];
        this.render();
    }
};
