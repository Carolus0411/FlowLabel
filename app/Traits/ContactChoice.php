<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Models\Contact;

trait ContactChoice
{
    public Collection $contactChoice;

    public function mountContactChoice()
    {
        $this->searchContact();
    }

    public function searchContact(string $value = ''): void
    {
        $selected = Contact::where('id', intval($this->contact_id ?? ''))->get();
        $this->contactChoice = Contact::query()
            ->filterLike('name', $value)
            ->isActive()
            ->take(20)
            ->get()
            ->merge($selected);
    }
}
