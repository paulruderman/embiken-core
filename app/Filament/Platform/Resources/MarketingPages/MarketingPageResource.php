<?php

namespace App\Filament\Platform\Resources\MarketingPages;

use App\Filament\Platform\Resources\MarketingPages\Pages\CreateMarketingPage;
use App\Filament\Platform\Resources\MarketingPages\Pages\EditMarketingPage;
use App\Filament\Platform\Resources\MarketingPages\Pages\ListMarketingPages;
use App\Filament\Platform\Resources\MarketingPages\Schemas\MarketingPageForm;
use App\Filament\Platform\Resources\MarketingPages\Tables\MarketingPagesTable;
use App\Models\MarketingPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketingPageResource extends Resource
{
    protected static ?string $model = MarketingPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Marketing content';

    protected static ?string $modelLabel = 'marketing page';

    protected static ?string $pluralModelLabel = 'marketing content';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return MarketingPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketingPagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketingPages::route('/'),
            'create' => CreateMarketingPage::route('/create'),
            'edit' => EditMarketingPage::route('/{record}/edit'),
        ];
    }
}
