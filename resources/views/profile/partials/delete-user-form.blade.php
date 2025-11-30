{{-- styled delete account section --}}
<section class="delete-account-section">
  <style>
    /* Section card */
    .delete-account-section { margin-bottom: 16px; }
    .delete-account-section header h2 { font-size: 18px; margin: 0 0 6px; color: #0f172a; font-weight: 600; }
    .delete-account-section header p { margin: 0 0 12px; color: #475569; font-size: 13px; }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 9px 14px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid transparent;
      text-decoration: none;
    }

    .btn-danger {
      background: #dc2626; /* red-600 */
      color: #ffffff;
      border-color: #b91c1c;
    }
    .btn-danger:hover,
    .btn-danger:focus {
      background: #b91c1c; /* red-700 */
      border-color: #7f1d1d;
      outline: none;
      box-shadow: 0 0 0 4px rgba(220,38,38,0.10);
    }

    .btn-secondary {
      background: #ffffff;
      color: #374151;
      border: 1px solid #d1d5db;
    }
    .btn-secondary:hover { background: #f8fafc; }

    /* Modal content (scoped minimal styles) */
    .modal-content {
      background: #ffffff;
      border-radius: 10px;
      padding: 20px;
      max-width: 640px;
      margin: 0 auto;
      box-shadow: 0 12px 40px rgba(2,6,23,0.12);
    }
    .modal-content h2 { margin: 0 0 8px; font-size: 18px; color: #0f172a; }
    .modal-content p { margin: 0 0 12px; color: #475569; font-size: 13px; }

    .modal-form-row { margin-top: 12px; }
    .modal-input {
      width: 75%;
      padding: 10px 12px;
      border: 1px solid #e6e9ef;
      border-radius: 8px;
      font-size: 14px;
      color: #0f172a;
      box-sizing: border-box;
    }
    .modal-error { color: #b91c1c; font-size: 13px; margin-top: 8px; }

    .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:18px; }
    @media (max-width:520px) {
      .modal-input { width:100%; }
      .modal-actions { flex-direction:column-reverse; align-items:stretch; }
      .btn { width:100%; }
    }
  </style>

  <header>
    <h2>{{ __('Delete Account') }}</h2>
    <p>{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}</p>
  </header>

  {{-- Trigger button: keep component but add class for styling --}}
  <x-danger-button
      x-data=""
      x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
      class="btn btn-danger"
  >
      {{ __('Delete Account') }}
  </x-danger-button>

  {{-- Modal: wrap content in a styled container so component markup inherits styles --}}
  <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <div class="modal-content">
      <form method="post" action="{{ route('profile.destroy') }}" class="p-0">
        @csrf
        @method('delete')

        <h2>{{ __('Are you sure you want to delete your account?') }}</h2>

        <p>{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}</p>

        <div class="modal-form-row">
          <label for="password" class="sr-only">{{ __('Password') }}</label>

          <x-text-input
              id="password"
              name="password"
              type="password"
              class="modal-input"
              placeholder="{{ __('Password') }}"
          />

          @if($errors->userDeletion && $errors->userDeletion->has('password'))
            <div class="modal-error">{{ $errors->userDeletion->first('password') }}</div>
          @endif
        </div>

        <div class="modal-actions">
          <x-secondary-button x-on:click="$dispatch('close')" class="btn btn-secondary">
            {{ __('Cancel') }}
          </x-secondary-button>

          {{-- Keep component but ensure it receives the danger class --}}
          <x-danger-button class="btn btn-danger">
            {{ __('Delete Account') }}
          </x-danger-button>
        </div>
      </form>
    </div>
  </x-modal>
</section>