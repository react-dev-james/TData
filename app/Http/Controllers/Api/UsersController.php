<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{

    public function __construct()
    {
        $this->middleware("auth");
    }

    /**
     * Return users for admin users management.
     * @param Request $request ['filter','search','sort','pager','page']
     * @return mixed
     */
    public function index(Request $request  )
    {
        $query =  \App\User::select( "id", "name", "email", "role", "created_at" );

        if ($request->has("sort")) {
            $sortBy = $request->sort['name'];
            $sortDirection = $request->sort['type'];
        }

        $query->orderBy($sortBy, $sortDirection);

        if ( $request->has( "pager" ) ) {
            $page = $request->pager['page'];
            $size = $request->pager['size'];
        }

        if ($request->has("search") && !empty($request->search)) {
            $query->where("name","like","%" . $request->search . "%");
            $query->orWhere("email","like","%" . $request->search . "%");
        }

        if ($request->has("filter") && !empty($request->filter)) {
            switch ($request->filter) {
                case "filter-admins":
                    $query->where("role","admin");
                    break;
                case "filter-clients":
                    $query->where("role","client");
                    break;
            }
        }

        return $query->paginate($size);
    }

    /**
     * Create a new user
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function create( Request $request )
    {

        $this->validate( $request, [
            'name'    => 'required|min:2|max:35',
            'role'    => 'required',
            'email'   => [
                'required',
                'email',
                'max:255',
                Rule::unique( 'users' )
            ],
            'password' => 'required|min:6|confirmed'
        ] );

        if ( !\Auth::user()->isAdmin() ) {
            return response()->json( [
                'message' => "You do not have permission to create this user.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ( \App\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role
            ])
        ) {
            return response()->json( [
                'message' => "User saved successfully.",
                'status'  => "success"
            ] );
        }

        return response()->json( [
            'message' => "Error saving user. Please Try Again.",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

    /**
     * Update a users profile
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function update(Request $request, User $user )
    {

        $this->validate($request, [
            'name'     => 'required|min:2|max:35',
            'email'    => [
                'required',
                'email',
                'max:255',
                Rule::unique( 'users' )->ignore( $user->id ),
            ],
            'role' => 'required',
        ]);

        if (\Auth::user()->id != $user->id && !\Auth::user()->isAdmin()) {
            return response()->json( [
                'message' => "You do not have permission to update this user.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $updateParams = [
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role
        ];

        /* Allow for password resets */
        if ($request->has('password') && $request->has('password_confirmation') && $request->password == $request->password_confirmation) {
            $updateParams['password'] = bcrypt($request->password);
        }

        if ( $user->update( $updateParams ) ) {
            return response()->json( [
                'message' => "User saved successfully.",
                'status'  => "success"
            ]);
        }

        return response()->json( [
            'message' => "Error saving profile. Please Try Again.",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

    public function loginAsUser( Request $request, User $user )
    {

        \Auth::loginUsingId( $user->id );
        return redirect( "/" );

    }

    /**
     * Delete a users profile
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function delete( Request $request, User $user )
    {

        if ( \Auth::user()->id != $user->id && !\Auth::user()->isAdmin() ) {
            return response()->json( [
                'message' => "You do not have permission to delete this user.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ( $user->delete() ) {
            return response()->json( [
                'message' => "User deleted successfully.",
                'status'  => "success"
            ] );
        }

        return response()->json( [
            'message' => "Error deleting user. Please Try Again.",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

    /**
     * Update a users profile, without validation
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function updateAll( Request $request, User $user )
    {
        if ( \Auth::user()->id != $user->id && !\Auth::user()->isAdmin() ) {
            return response()->json( [
                'message' => "You do not have permission to update this user.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /* Do not allow users to make themselves admins */
        if (!\Auth::user()->isAdmin() && $request->role == "admin") {
            $request->role = \Auth::user()->role;
        }

        if ( $user->update( $request->except( "id,updated_at,created_at" ) ) ) {
            return response()->json( [
                'message' => "Profile saved successfully.",
                'status'  => "success",
                'user' => $user
            ] );
        }

        return response()->json( [
            'message' => "Error saving profile. Please Try Again.",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

    /**
     * Update a users role
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function updateRole( Request $request, User $user )
    {
        if ( \Auth::user()->id != $user->id && !\Auth::user()->isAdmin() ) {
            return response()->json( [
                'message' => "You do not have permission to perform this action.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /* Do not allow users to make themselves admins */
        if ( !\Auth::user()->isAdmin() && $request->role == "admin" ) {
            $request->role = \Auth::user()->role;
        }

        if ( $user->update( $request->except( "id,updated_at,created_at" ) ) ) {
            return response()->json( [
                'message' => "Your user role has been updated successfully.",
                'status'  => "success",
                'user'    => $user
            ] );
        }

        return response()->json( [
            'message' => "Error updating your user role. Please try again",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

}
