<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('papyrus:create-user')]
#[Description('Créer un compte utilisateur Papyrus')]
class CreateUser extends Command
{
    public function handle(): int
    {
        $this->info('Création d\'un nouveau compte Papyrus');
        $this->newLine();

        $name = $this->ask('Nom');

        $email = $this->ask('Email');
        $validator = Validator::make(['email' => $email], ['email' => 'required|email|unique:users,email']);
        if ($validator->fails()) {
            $this->error('Email invalide ou déjà utilisé.');
            return self::FAILURE;
        }

        $password = $this->secret('Mot de passe (min. 8 caractères)');
        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit faire au moins 8 caractères.');
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->newLine();
        $this->info("Compte créé : {$user->name} <{$user->email}>");

        return self::SUCCESS;
    }
}
