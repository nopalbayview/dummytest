<?php

use CodeIgniter\Router\RouteCollection;

// Middleware Login
$this->auth = ['filter' => 'auth'];
$this->noauth = ['filter' => 'noauth'];

/**
 * @var RouteCollection $routes
 */
$routes->add('/', 'User::viewLogin', $this->auth);


// Login
$routes->group('login', function ($routes) {
    $routes->add('', 'User::viewLogin', $this->auth);
    $routes->add('auth', 'User::loginAuth', $this->auth);
});
// Routes Master User
$routes->group('user', function ($routes) {
    $routes->add('', 'User::index', $this->noauth);
    $routes->add('table', 'User::datatable', $this->noauth);
    $routes->add('add', 'User::addData', $this->noauth);
    $routes->add('form', 'User::forms', $this->noauth);
    $routes->add('form/(:any)', 'User::forms/$1', $this->noauth);
    $routes->add('update', 'User::updateData', $this->noauth);
    $routes->add('delete', 'User::deleteData', $this->noauth);
    $routes->add('printpdf', 'User::printPDF', $this->noauth);
});

//document ROUTES
$routes->group('document', function ($routes) {
    $routes->add('', 'document::index', $this->noauth);
    $routes->add('table', 'document::datatable', $this->noauth);
    $routes->add('add', 'document::addData', $this->noauth);
    $routes->add('form', 'document::forms', $this->noauth);
    $routes->add('form/(:any)', 'document::forms/$1', $this->noauth);
    $routes->add('update', 'document::updateData', $this->noauth);
    $routes->add('delete', 'document::deleteData', $this->noauth);
});


$routes->group('customer', function ($routes) {
    $routes->add('', 'Customer::index', $this->noauth);
    $routes->add('table', 'Customer::datatable', $this->noauth);
    $routes->add('add', 'Customer::addData', $this->noauth);
    $routes->add('form', 'Customer::forms', $this->noauth); // Form tanpa parameter
    $routes->add('form/(:num)', 'Customer::forms/$1', $this->noauth); // Form dengan parameter
    $routes->add('update', 'Customer::updateData', $this->noauth);
    $routes->add('exportexcel', 'Customer::exportExcel', $this->noauth);
    $routes->add('printpdf', 'Customer::printPDF', $this->noauth);
    $routes->add('delete', 'Customer::deleteData', $this->noauth);
});
// Routes Master Category
$routes->group('category', function ($routes) {
    $routes->add('', 'Category::index', $this->noauth);
    $routes->add('table', 'Category::datatable', $this->noauth);
    $routes->add('add', 'Category::addData', $this->noauth);
    $routes->add('form', 'Category::forms', $this->noauth);
    $routes->add('form/(:any)', 'Category::forms/$1', $this->noauth);
    $routes->add('update', 'Category::updateData', $this->noauth);
    $routes->add('delete', 'Category::deleteData', $this->noauth);
    $routes->add('export', 'Category::export', $this->noauth);
    $routes->add('exportPdf', 'Category::exportPdf', $this->noauth);
    $routes->add('formImport', 'Category::importExcel', $this->noauth);
    $routes->add('importExcel', 'Category::importExcel', $this->noauth);
});


// Routes Master Supplier
$routes->group('supplier', function ($routes) {
    $routes->add('/', 'Supplier::index', $this->noauth);
    $routes->add('table', 'Supplier::dataTable', $this->noauth);
    $routes->add('forms', 'Supplier::forms', $this->noauth);
    $routes->add('form/(:any)', 'Supplier::forms/$1', $this->noauth);
    $routes->add('add', 'Supplier::add', $this->noauth);
    $routes->add('update', 'Supplier::update', $this->noauth);
    $routes->add('export', 'Supplier::exportexcel', $this->noauth);
    $routes->add('delete', 'Supplier::delete', $this->noauth);
    $routes->add('pdf', 'Supplier::Fpdf', $this->noauth);
    $routes->add('pdf/(:any)', 'Supplier::Fpdf/$1', $this->noauth);
});

// Routes Master Project
$routes->group('project', function ($routes) {
    $routes->add('', 'Project::index', $this->noauth);
    $routes->add('table', 'Project::datatable', $this->noauth);
    $routes->add('add', 'Project::addData', $this->noauth);
    $routes->add('form', 'Project::forms', $this->noauth);
    $routes->add('form/(:any)', 'Project::forms/$1', $this->noauth);
    $routes->add('update', 'Project::updateData', $this->noauth);
    $routes->add('delete', 'Project::deleteData', $this->noauth);
    $routes->add('export', 'Project::exportexcel');
    $routes->add('formImport', 'Invoice::formImport', $this->noauth);
    $routes->get('generatePdf', 'Project::generatePdf');
    $routes->get('generatePdf/(:any)', 'Project::generatePdf/$1', $this->noauth);
});
// Routes Master Product
$routes->group('product', function ($routes) {
    $routes->add('', 'Product::index', $this->noauth);
    $routes->add('table', 'Product::datatable', $this->noauth);
    $routes->add('add', 'Product::addData', $this->noauth);
    $routes->add('form', 'Product::forms', $this->noauth);
    $routes->add('form/(:any)', 'Product::forms/$1', $this->noauth);
    $routes->add('update', 'Product::updateData', $this->noauth);
    $routes->add('export', 'Product::exportexcel', $this->noauth);
    $routes->add('pdf', 'Product::Fpdf', $this->noauth);
    $routes->add('delete', 'Product::deleteData', $this->noauth);
    $routes->add('formImport', 'Product::formImport', $this->noauth);
    $routes->add('importExcel', 'Product::importExcel', $this->noauth);
});

// Routes Master Invoice
$routes->group('invoice', function ($routes) {
    $routes->add('', 'Invoice::index', $this->noauth);
    $routes->add('table', 'Invoice::datatable', $this->noauth);
    $routes->add('add', 'Invoice::addData', $this->noauth);
    $routes->add('form', 'Invoice::forms', $this->noauth);
    $routes->add('form/(:any)', 'Invoice::forms/$1', $this->noauth);
    $routes->add('update', 'Invoice::updateData', $this->noauth);
    $routes->add('export', 'Invoice::exportExcel', $this->noauth);
    $routes->add('pdf', 'Invoice::Fpdf', $this->noauth);
    $routes->add('delete', 'Invoice::deleteData', $this->noauth);
    $routes->add('getDetails', 'Invoice::getDetails', $this->noauth);
    $routes->add('addDetail', 'Invoice::addDetail', $this->noauth);
    $routes->add('updateDetail', 'Invoice::updateDetail', $this->noauth);
    $routes->add('deleteDetail', 'Invoice::deleteDetail', $this->noauth);
    $routes->add('detailDatatable', 'Invoice::detailDatatable', $this->noauth);
    $routes->add('detailDatatable/(:any)', 'Invoice::detailDatatable/$1', $this->noauth);
    $routes->add('detailform/(:any)', 'Invoice::detailForm/$1', $this->noauth);
    $routes->add('customerList', 'Invoice::customerList', $this->noauth);
    $routes->add('getProducts', 'Invoice::getProducts', $this->noauth);
    $routes->add('getUOMs', 'Invoice::getUOMs', $this->noauth);
    $routes->add('printPDF', 'Invoice::printPDF', $this->noauth);
    $routes->add('pdf/(:any)', 'Invoice::printPDF/$1', $this->noauth);
    $routes->add('invoice/pdf/(:any)', 'Invoice::printPDF/$1', $this->noauth);
    $routes->add('customer/list', 'Invoice::customerList', $this->noauth);
    $routes->add('product/list', 'Invoice::productList', $this->noauth);
    $routes->add('uomList', 'Invoice::uomList', $this->noauth);
    $routes->add('updateGrandTotal', 'Invoice::updateGrandTotal', $this->noauth);
    $routes->add('getHeaderChunk', 'Invoice::getHeaderChunk', $this->noauth);
    // Customer list routes
});
// -------------------------------------------------------->
// Log Out
$routes->add('/logout', 'User::logOut');

//Export to excel routes
$routes->get('Document/export', 'Document::export');
$routes->get('Document/exportpdf', 'Document::exportpdf');
