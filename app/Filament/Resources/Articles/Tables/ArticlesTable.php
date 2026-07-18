<?php

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                TextColumn::make('summary')->label('Ringkasan')->limit(60)->toggleable(),
                IconColumn::make('is_published')->label('Publish')->boolean(),
                TextColumn::make('published_at')->label('Tanggal Publikasi')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                Action::make('view_public')
                    ->label('Lihat Publik')
                    ->url(fn ($record): string => route('articles.show', $record))
                    ->visible(fn ($record): bool => $record->is_published && $record->published_at?->isPast())
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
