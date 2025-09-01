<?php

namespace App\Filament\Pages\App;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Filament\Actions\GeneratePasswordAction;
use Filament\Forms\Components\TextInput;

class Profile extends EditProfile
{
    public function getBreadcrumbs(): array
    {
        return [
            null => __('Dashboard'),
            'profile' => __('Profile'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        /** @var TextInput $passwordComponent */
        $passwordComponent = $this->getPasswordFormComponent();

        return $schema->components([
            Section::make()
                ->inlineLabel(false)
                ->schema([
                    $this->getNameFormComponent(),
                    $this->getEmailFormComponent(),
                    $passwordComponent->suffixActions([
                        GeneratePasswordAction::make(),
                    ]),
                    $this->getPasswordConfirmationFormComponent(),
                ]),
        ]);
    }
}
