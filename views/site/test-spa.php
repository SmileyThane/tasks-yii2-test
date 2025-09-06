<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Test SPA';
$this->params['breadcrumbs'][] = $this->title;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <nav class="navbar navbar-expand-lg bg-body border-bottom shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="#">Login</a>
            <div class="ms-auto d-flex gap-2">
                <div class="input-group" style="max-width: 520px;">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input data-testid="email" id="email" class="form-control" placeholder="email" value="admin@example.com">
                    <input data-testid="password" id="password" class="form-control" placeholder="password" type="password" value="admin123">
                    <button class="btn btn-primary" id="loginBtn" data-testid="login"><i class="bi bi-box-arrow-in-right"></i></button>
                    <button class="btn btn-outline-secondary" id="logoutBtn" data-testid="logout"><i class="bi bi-box-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </nav>

    <div class="my-3 small text-muted">
        Token: <code id="tokenEcho">—</code>
    </div>

    <div class="sticky-toolbar py-2">
        <div class="container-fluid">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label mb-1">Status</label>
                    <select id="fltStatus" data-testid="flt-status" class="form-select form-select-sm" multiple size="3" style="min-width: 180px;">
                        <option value="pending">pending</option>
                        <option value="in_progress">in_progress</option>
                        <option value="completed">completed</option>
                    </select>
                </div>

                <div>
                    <label class="form-label mb-1">Priority</label>
                    <select id="fltPriority" data-testid="flt-priority" class="form-select form-select-sm" multiple size="3" style="min-width: 160px;">
                        <option value="low">low</option>
                        <option value="medium">medium</option>
                        <option value="high">high</option>
                    </select>
                </div>

                <div>
                    <label class="form-label mb-1">Tags</label>
                    <select id="fltTags" data-testid="flt-tags" class="form-select form-select-sm" multiple size="3" style="min-width: 200px;"></select>
                </div>

                <div>
                    <label class="form-label mb-1">Search</label>
                    <input id="fltQ" data-testid="flt-q" class="form-control form-control-sm" placeholder="title/description" style="min-width: 220px;">
                </div>

                <div>
                    <label class="form-label mb-1">Sort</label>
                    <select id="fltSort" data-testid="flt-sort" class="form-select form-select-sm" style="min-width: 170px;">
                        <option value="-due_date,title" selected>due_date DESC, title</option>
                        <option value="-created_at">created_at DESC</option>
                        <option value="title">title ASC</option>
                        <option value="-priority">priority DESC</option>
                    </select>
                </div>

                <div id="pageBox">
                    <label class="form-label mb-1">Pagination</label>
                    <div class="input-group input-group-sm" style="width:220px;">
                        <span class="input-group-text">page</span>
                        <input id="fltPage" data-testid="flt-page" type="number" class="form-control" value="1" min="1">
                        <span class="input-group-text">per</span>
                        <input id="fltPer" data-testid="flt-per" type="number" class="form-control" value="10" min="1">
                    </div>
                </div>

                <div class="ms-auto d-flex gap-2 mt-4">
                    <button class="btn btn-sm btn-outline-secondary" id="resetBtn">Clear</button>
                    <button class="btn btn-sm btn-primary" id="applyBtn" data-testid="load"><i class="bi bi-arrow-repeat me-1"></i>Reload</button>
                    <button class="btn btn-sm btn-success" id="openCreate" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-circle me-1"></i>Create
                    </button>
                </div>
            </div>
            <div id="listMsg" class="small text-muted mt-1"></div>
        </div>
    </div>

    <main class="container-fluid mt-3">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tasksTable">
                    <thead class="table-light">
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Title</th>
                        <th style="width:140px;">Status</th>
                        <th style="width:120px;">Priority</th>
                        <th style="width:140px;">Due</th>
                        <th style="width:220px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="tasksBody">
                    <tr><td colspan="8" class="text-center py-4 text-muted">No data</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="small text-muted" id="metaEcho">—</div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" id="prevBtn"><i class="bi bi-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary" id="nextBtn"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="createForm">
                <div class="modal-header">
                    <h5 class="modal-title">Create task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input id="newTitle" data-testid="new-title" class="form-control" required minlength="5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due date</label>
                            <input id="newDue" data-testid="new-due" type="date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select id="newStatus" data-testid="new-status" class="form-select">
                                <option value="pending">pending</option>
                                <option value="in_progress">in_progress</option>
                                <option value="completed">completed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select id="newPriority" data-testid="new-priority" class="form-select">
                                <option value="low">low</option>
                                <option value="medium" selected>medium</option>
                                <option value="high">high</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Assigned to (user id)</label>
                            <input id="newAssignee" data-testid="new-assignee" type="number" class="form-control" placeholder="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tags</label>
                            <select id="newTags" data-testid="new-tags" class="form-select" multiple size="6"></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea id="newDesc" class="form-control" rows="3" placeholder="..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="small text-muted" id="createMsg">—</div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" data-testid="create"><i class="bi bi-check2-circle me-1"></i>Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit task <span id="editIdEcho" class="text-muted"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId">
                    <input type="hidden" id="editVersion">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input id="editTitle" class="form-control" required minlength="5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due date</label>
                            <input id="editDue" type="date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select id="editStatus" class="form-select">
                                <option value="pending">pending</option>
                                <option value="in_progress">in_progress</option>
                                <option value="completed">completed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select id="editPriority" class="form-select">
                                <option value="low">low</option>
                                <option value="medium">medium</option>
                                <option value="high">high</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Assigned to</label>
                            <input id="editAssignee" type="number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tags</label>
                            <select id="editTags" class="form-select" multiple size="6"></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea id="editDesc" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="small text-muted" id="editMsg">—</div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="/js/test-spa.js" />