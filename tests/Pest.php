<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Os testes de Feature precisam estender o TestCase do Laravel (para usar
 * $this->get(), actingAs(), etc.) e recriar o banco a cada teste.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
