<?php
namespace App\Services;

use App\Models\Contact;

class ContactService
{
    public function getAllContacts()
    {
        return Contact::orderBy('created_at', 'DESC')->paginate(10);
    }

    public function deleteContact($id)
    {
        $contact = Contact::find($id);
        if ($contact) {
            $contact->delete();
        }
    }
}
