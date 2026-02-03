<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContatoExterno extends Model
{
    use HasFactory;

    protected $table = 'contatos_externos';

    protected $fillable = [
        'nome',
        'telefone',
        'origem',
        'google_contact_id',
    ];

    public function setTelefoneAttribute(?string $value): void
    {
        $this->attributes['telefone'] = $value ? Phone::normalize($value) : null;
    }
}
