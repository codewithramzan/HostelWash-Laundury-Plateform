<?php

use app\Controllers\AccountController;
use app\Controllers\AdminController;
use app\Controllers\AuthController;
use app\Controllers\DashboardController;
use app\Controllers\OrderController;
use app\Controllers\PublicController;

return [
    ['GET', '#^/$#', fn () => (new PublicController())->page()],
    ['GET', '#^/(services|pricing|how-it-works|hostels|about|faq|contact|terms|privacy)$#', fn ($page) => (new PublicController())->page($page)],
    ['GET', '#^/(login|register|forgot|reset|verify)$#', fn ($mode) => (new AuthController())->form($mode)],
    ['POST', '#^/login$#', fn () => (new AuthController())->login()],
    ['POST', '#^/register$#', fn () => (new AuthController())->register()],
    ['POST', '#^/forgot$#', fn () => (new AuthController())->forgot()],
    ['POST', '#^/reset$#', fn () => (new AuthController())->reset()],
    ['POST', '#^/verify$#', fn () => (new AuthController())->verify()],
    ['POST', '#^/logout$#', fn () => (new AuthController())->logout()],
    ['GET', '#^/dashboard$#', fn () => (new DashboardController())->index()],
    ['GET', '#^/orders$#', fn () => (new OrderController())->index()],
    ['GET', '#^/orders/new$#', fn () => (new OrderController())->create()],
    ['POST', '#^/orders$#', fn () => (new OrderController())->store()],
    ['GET', '#^/orders/(\d+)$#', fn ($id) => (new OrderController())->show((int) $id)],
    ['POST', '#^/orders/(\d+)/status$#', fn ($id) => (new OrderController())->update((int) $id)],
    ['POST', '#^/orders/(\d+)/assign$#', fn ($id) => (new OrderController())->assign((int) $id)],
    ['POST', '#^/orders/(\d+)/payment$#', fn ($id) => (new OrderController())->payment((int) $id)],
    ['GET', '#^/profile$#', fn () => (new AccountController())->profile()],
    ['POST', '#^/profile$#', fn () => (new AccountController())->saveProfile()],
    ['GET', '#^/payments$#', fn () => (new AccountController())->payments()],
    ['GET', '#^/notifications$#', fn () => (new AccountController())->notifications()],
    ['POST', '#^/notifications/read$#', fn () => (new AccountController())->readNotifications()],
    ['GET', '#^/support$#', fn () => (new AccountController())->support()],
    ['POST', '#^/support$#', fn () => (new AccountController())->sendSupport()],
    ['POST', '#^/support/(\d+)$#', fn ($id) => (new AccountController())->replySupport((int) $id)],
    ['GET', '#^/admin/users$#', fn () => (new AdminController())->users()],
    ['POST', '#^/admin/users$#', fn () => (new AdminController())->saveUser()],
    ['GET', '#^/admin/(hostels|rooms|services|representatives|expenses|coupons)$#', fn ($resource) => (new AdminController())->resource($resource)],
    ['POST', '#^/admin/(hostels|rooms|services|representatives|expenses|coupons)$#', fn ($resource) => (new AdminController())->saveResource($resource)],
    ['GET', '#^/admin/(settings|content)$#', fn ($section) => (new AdminController())->settings($section)],
    ['POST', '#^/admin/settings$#', fn () => (new AdminController())->saveSettings()],
    ['GET', '#^/admin/reports$#', fn () => (new AdminController())->reports()],
];
