<?php

namespace App\Filament\Support;

use App\Support\Icons;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\HtmlString;

/**
 * Side-by-side English / Amharic inputs. English goes to `field`, Amharic to `field_am`.
 */
final class Bilingual
{
    public static function text(string $name, string $label, bool $required = false, int $max = 255, ?string $helper = null): Grid
    {
        return Grid::make(['default' => 1, 'md' => 2])->schema([
            TextInput::make($name)->label($label)->required($required)->maxLength($max)->helperText($helper),
            TextInput::make($name.'_am')->label($label.' · አማርኛ')->maxLength($max)->extraInputAttributes(['lang' => 'am']),
        ])->columnSpanFull();
    }

    public static function textarea(string $name, string $label, bool $required = false, int $max = 1000, int $rows = 3): Grid
    {
        return Grid::make(['default' => 1, 'md' => 2])->schema([
            Textarea::make($name)->label($label)->required($required)->maxLength($max)->rows($rows),
            Textarea::make($name.'_am')->label($label.' · አማርኛ')->maxLength($max)->rows($rows)->extraInputAttributes(['lang' => 'am']),
        ])->columnSpanFull();
    }

    public static function rich(string $name, string $label, string $directory = 'content'): Tabs
    {
        $editor = fn (string $field, string $l) => RichEditor::make($field)
            ->label($l)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                ['h2', 'h3', 'blockquote', 'bulletList', 'orderedList', 'horizontalRule'],
                ['attachFiles', 'table'],
                ['undo', 'redo'],
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory($directory)
            ->fileAttachmentsVisibility('public');

        return Tabs::make($label)->tabs([
            Tab::make('English')->schema([$editor($name, $label)]),
            Tab::make('አማርኛ')->schema([$editor($name.'_am', $label.' · አማርኛ')]),
        ])->columnSpanFull();
    }

    public static function iconSelect(string $name = 'icon'): Select
    {
        $options = [];
        foreach (Icons::options() as $key => $label) {
            $options[$key] = '<span style="display:inline-flex;align-items:center;gap:.5rem"><span style="width:1.1rem;height:1.1rem;display:inline-block;color:#0b3d2a">'.Icons::svg($key, '', 1.8).'</span>'.e($label).'</span>';
        }

        return Select::make($name)
            ->label('Icon')
            ->options($options)
            ->allowHtml()
            ->searchable()
            ->native(false)
            // Never save an empty icon (the card needs one) — fall back to the first icon.
            ->default(array_key_first($options))
            ->dehydrateStateUsing(fn ($state) => $state ?: array_key_first($options));
    }

    public static function amharicHint(): HtmlString
    {
        return new HtmlString('<span style="font-size:.8rem;color:#6b7280">Leave the Amharic (አማርኛ) field empty to show the English text on the Amharic site.</span>');
    }
}
