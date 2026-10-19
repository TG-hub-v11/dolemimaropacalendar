Merge into Laravel's generated `app/Models/User.php` (the bootstrap script appends the trait):
`use App\Models\Concerns\HasOfficeAccess;` and `use HasApiTokens, HasOfficeAccess;` and add
`primary_office_id, position_title, is_active` to `$fillable`; never add them to anything user-editable via the profile endpoint.
