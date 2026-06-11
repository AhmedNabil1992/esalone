<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Profile')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email address'),
                        TextEntry::make('email_verified_at')
                            ->dateTime()
                            ->placeholder('Not verified'),
                    ])
                    ->columns(3),

                Section::make('Access Control')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->separator(',')
                            ->placeholder('No roles assigned')
                            ->columnSpanFull(),
                        TextEntry::make('permissions.name')
                            ->label('Direct Permissions')
                            ->badge()
                            ->separator(',')
                            ->placeholder('No direct permissions assigned')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Security & Audit')
                    ->schema([
                        TextEntry::make('two_factor_secret')
                            ->label('Two-factor secret')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Configured' : 'Not configured'),
                        TextEntry::make('two_factor_recovery_codes')
                            ->label('Recovery codes')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Configured' : 'Not configured'),
                        TextEntry::make('two_factor_confirmed_at')
                            ->dateTime()
                            ->placeholder('Not confirmed'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
