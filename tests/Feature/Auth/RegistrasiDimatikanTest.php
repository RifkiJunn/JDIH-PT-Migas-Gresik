<?php

it('menolak registrasi mandiri', fn () => $this->get('/register')->assertNotFound());

it('menolak lupa password mandiri', fn () => $this->get('/forgot-password')->assertNotFound());
