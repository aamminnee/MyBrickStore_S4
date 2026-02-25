document.getElementById('item_search').addEventListener('input', function () {
    const val  = this.value;
    const opts = document.getElementById('items_list').childNodes;
    let found  = false;

    for (let i = 0; i < opts.length; i++) {
        if (opts[i].value === val) {
            const id = val.split(' - ')[0];
            document.getElementById('real_item_id').value = id;
            found = true;
            break;
        }
    }

    if (!found && val === '') {
        document.getElementById('real_item_id').value = '';
    }
});

document.querySelector('.form-admin').addEventListener('submit', function (e) {
    const id = document.getElementById('real_item_id').value;
    if (!id) {
        e.preventDefault();
        alert(STOCK_CONFIG.i18n.invalidItem);
    }
});

document.querySelector('.table-stock tbody').addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-stock-edit');
    if (!btn) return;

    e.preventDefault();

    const id    = btn.dataset.id;
    const label = btn.dataset.label;

    document.getElementById('item_search').value    = id + ' - ' + label;
    document.getElementById('real_item_id').value   = id;

    window.scrollTo({ top: 0, behavior: 'smooth' });
});