const API_BASE = '/api';
const axiosApi = axios.create({ baseURL: API_BASE, timeout: 15000 });

let ACCESS_TOKEN = '';
function setToken(t) {
    ACCESS_TOKEN = t || '';
    document.getElementById('tokenEcho').textContent = ACCESS_TOKEN ? (ACCESS_TOKEN.slice(0, 20) + '…') : '—';
    document.getElementById('logoutBtn').disabled = !ACCESS_TOKEN;
}
axiosApi.interceptors.request.use((cfg) => {
    if (ACCESS_TOKEN) cfg.headers.Authorization = 'Bearer ' + ACCESS_TOKEN;
    cfg.headers.Accept = 'application/json';
    return cfg;
});
axiosApi.interceptors.response.use(
    res => res,
    err => {
        const status = err?.response?.status || 0;
        const data = err?.response?.data || {};
        return Promise.reject({ status, data });
    }
);

const $ = (id) => document.getElementById(id);
const selectedValues = (selectEl) => Array.from(selectEl.selectedOptions).map(o => o.value);
const setSelected = (selectEl, values=[]) => Array.from(selectEl.options).forEach(o => o.selected = values.includes(String(o.value)));
const fmtDate = (s) => s ? new Date(s + 'T00:00:00').toLocaleDateString() : '';

function badgeStatus(s) {
    const map = { pending:'secondary', in_progress:'info', completed:'success' };
    return `<span class="badge bg-${map[s]||'secondary'} badge-status">${s||''}</span>`;
}
function badgePriority(p) {
    const map = { low:'secondary', medium:'primary', high:'danger' };
    return `<span class="badge bg-${map[p]||'secondary'} badge-priority">${p||''}</span>`;
}
function chips(tags) {
    return (tags||[]).map(t => `<span class="tag-chip">${t.name}</span>`).join('');
}

async function login() {
    const email = $('email').value.trim();
    const password = $('password').value;
    $('loginBtn').disabled = true;
    try {
        const { data } = await axiosApi.post('/auth/login', { email, password });
        await setToken(data.access_token);
        await loadList();
        await loadTags();
    } catch (e) { alert('Login error ' + e.status + '\n' + JSON.stringify(e.data)); }
    finally { $('loginBtn').disabled = false; }
}
async function logout() {
    $('logoutBtn').disabled = true;
    try {
        await axiosApi.post('/auth/logout');
    } catch (e) { /* ignore */ }
    setToken('');
}

async function loadTags() {
    try {
        const { data } = await axiosApi.get('/tags');
        const list = data.items || data; // support both
        const fills = ['fltTags','newTags','editTags'].map($);
        for (const sel of fills) {
            sel.innerHTML = '';
            list.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id; opt.textContent = `${t.name} (#${t.id})`;
                sel.appendChild(opt);
            });
        }
    } catch (e) {
        console.warn('tags load failed', e);
    }
}

let lastMeta = null;

function collectFilters() {
    const params = new URLSearchParams();
    const statuses = selectedValues($('fltStatus'));
    const prios    = selectedValues($('fltPriority'));
    const tagIds   = selectedValues($('fltTags'));

    if (statuses.length) params.set('status', statuses.join(','));
    if (prios.length)    params.set('priority', prios.join(','));
    if (tagIds.length)   params.set('tags', tagIds.join(','));

    const q = $('fltQ').value.trim(); if (q) params.set('q', q);
    const sort = $('fltSort').value; if (sort) params.set('sort', sort);

    params.set('page', $('fltPage').value || '1');
    params.set('per-page', $('fltPer').value || '10');

    return params;
}

function renderList(payload) {
    const tbody = $('tasksBody');
    tbody.innerHTML = '';
    const items = payload.items || [];
    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No data</td></tr>`;
    }
    items.forEach(t => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-testid', `task-row-${t.id}`);
        tr.innerHTML = `
      <td class="text-muted" onclick="loadItem(${t.id})">#${t.id}</td>
      <td>${escapeHtml(t.title||'')}</td>
      <td>${badgeStatus(t.status)}</td>
      <td>${badgePriority(t.priority)}</td>
      <td>${fmtDate(t.due_date)}</td>
      <td class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-secondary" title="Edit" onclick="openEdit(${t.id})"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-primary" data-testid="toggle-${t.id}" title="Toggle status" onclick="toggleStatus(${t.id})"><i class="bi bi-arrow-repeat"></i></button>
        <button class="btn btn-sm btn-outline-danger" data-testid="delete-${t.id}" title="Delete (soft)" onclick="deleteTask(${t.id})"><i class="bi bi-trash"></i></button>
        <button class="btn btn-sm btn-outline-success" data-testid="restore-${t.id}" title="Restore" onclick="restoreTask(${t.id})"><i class="bi bi-arrow-counterclockwise"></i></button>
      </td>
    `;
        tbody.appendChild(tr);
    });

    lastMeta = payload._meta || null;

    if (lastMeta) {
        const { currentPage, pageCount, totalCount, perPage } = lastMeta;
        $('metaEcho').textContent = `page ${currentPage}/${pageCount} • total ${totalCount} • per ${perPage}`;
        $('prevBtn').disabled = currentPage <= 1;
        $('nextBtn').disabled = currentPage >= pageCount;
    } else {
        $('metaEcho').textContent = '—';
        $('prevBtn').disabled = true;
        $('nextBtn').disabled = true;
    }
}

async function loadList() {
    $('applyBtn').disabled = true;
    $('listMsg').textContent = 'Loading...';
    try {
        const params = collectFilters();
        const { data } = await axiosApi.get('/tasks', { params });
        renderList(data);
        $('listMsg').textContent = `${(data.items||[]).length} items`;
    } catch (e) {
        $('listMsg').innerHTML = `<span class="text-danger">Error ${e.status}</span>`;
    } finally {
        $('applyBtn').disabled = false;
    }
}

async function loadItem(id) {
    try {
        const { data } = await axiosApi.get(`/tasks/${id}`);
        console.info(data)
    } catch (e) {
        console.error(e)
    }
}

async function toggleStatus(id) {
    try {
        await axiosApi.patch(`/tasks/${id}/toggle-status`);
        await loadList();
    } catch (e) { alert('Toggle error ' + e.status); }
}
async function deleteTask(id) {
    if (!confirm(`Delete task #${id}? (soft delete)`)) return;
    try {
        await axiosApi.delete(`/tasks/${id}`);
        await loadList();
    } catch (e) { alert('Delete error ' + e.status); }
}
async function restoreTask(id) {
    try {
        await axiosApi.patch(`/tasks/${id}/restore`);
        await loadList();
    } catch (e) { alert('Restore error ' + e.status); }
}

$('createForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const title = $('newTitle').value.trim();
    const due   = $('newDue').value;
    const status= $('newStatus').value;
    const prio  = $('newPriority').value;
    const ass   = $('newAssignee').value ? Number($('newAssignee').value) : null;
    const tags  = selectedValues($('newTags')).map(Number);
    const desc  = $('newDesc').value;

    $('createMsg').textContent = '…';
    try {
        const body = { title, due_date: due, status, priority: prio, assigned_to: ass, tags, description: desc };
        const { data } = await axiosApi.post('/tasks', body);
        $('createMsg').innerHTML = `<span class="text-success">Created #${data.id}</span>`;
        await loadList();
    } catch (e) {
        $('createMsg').innerHTML = `<span class="text-danger">Error ${e.status}: ${escapeHtml(JSON.stringify(e.data))}</span>`;
    }
});

async function openEdit(id) {
    try {
        const { data } = await axiosApi.get(`/tasks/${id}`);
        $('editId').value = data.id;
        $('editVersion').value = data.version;
        $('editIdEcho').textContent = `#${data.id}`;
        $('editTitle').value = data.title || '';
        $('editDue').value = (data.due_date || '').substring(0,10);
        $('editStatus').value = data.status || 'pending';
        $('editPriority').value = data.priority || 'medium';
        $('editAssignee').value = data.assigned_to || '';
        $('editDesc').value = data.description || '';
        setSelected($('editTags'), (data.tags||[]).map(t => String(t.id)));
        $('editMsg').textContent = '—';
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();

    } catch (e) { alert('Load error ' + e); }
}
$('editForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const id = Number($('editId').value);
    const body = {
        title: $('editTitle').value.trim(),
        due_date: $('editDue').value || null,
        status: $('editStatus').value,
        priority: $('editPriority').value,
        assigned_to: $('editAssignee').value ? Number($('editAssignee').value) : null,
        description: $('editDesc').value,
        tags: selectedValues($('editTags')).map(Number),
        version: Number($('editVersion').value),
    };
    $('editMsg').textContent = '…';
    try {
        const { data } = await axiosApi.put(`/tasks/${id}`, body);
        $('editMsg').innerHTML = `<span class="text-success">Saved</span>`;
        await loadList();
    } catch (e) {
        $('editMsg').innerHTML = `<span class="text-danger">Error ${e.status}: ${escapeHtml(JSON.stringify(e.data))}</span>`;
    }
});

$('loginBtn').addEventListener('click', login);
$('logoutBtn').addEventListener('click', logout);
$('applyBtn').addEventListener('click', () => {loadList(); loadTags();});
$('resetBtn').addEventListener('click', () => {
    $('fltStatus').selectedIndex = -1;
    $('fltPriority').selectedIndex = -1;
    $('fltTags').selectedIndex = -1;
    $('fltQ').value = '';
    $('fltSort').value = '-due_date,title';
    $('fltPage').value = 1;
    $('fltPer').value = 10;
});
$('prevBtn').addEventListener('click', async () => {
    const curr = Number($('fltPage').value||1);
    if (curr > 1) { $('fltPage').value = String(curr-1); await loadList(); }
});
$('nextBtn').addEventListener('click', async () => {
    const curr = Number($('fltPage').value||1);
    $('fltPage').value = String(curr+1);
    await loadList();
});



function escapeHtml(s) {
    return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

(async function init() {
    setToken('');
    const in3 = new Date(Date.now()+3*86400000).toISOString().slice(0,10);
    $('newDue').value = in3;

    await loadTags();
    await loadList();
})();