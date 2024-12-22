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
});
// Routing Master Dibawah Sini ---------------------------
$routes->group('category', function ($routes) { 
    $routes->add('', 'Category::index', $this->noauth);
    $routes->add('table', 'Category::datatable', $this->noauth);
    $routes->add('add', 'Category::addData', $this->noauth);
    $routes->add('form', 'Category::forms', $this->noauth); // Form tanpa parameter
    $routes->add('form/(:any)', 'Category::forms/$1', $this->noauth); // Form dengan parameter
    $routes->add('update', 'Category::updateData', $this->noauth);
    $routes->add('delete', 'Category::deleteData', $this->noauth);
});
// -------------------------------------------------------->
// Log Out
$routes->add('logout', 'User::logOut');
