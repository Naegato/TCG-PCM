"use client";

import { useActionState, useState } from "react";
import { useFormStatus } from "react-dom";

import { loginAction, type AuthActionState } from "@/lib/actions/auth";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Field, FieldGroup, FieldLabel, FieldError } from "@/components/ui/field";

const initialState: AuthActionState = { error: null };

function SubmitButton() {
	const { pending } = useFormStatus();

	return (
		<Button type="submit" disabled={pending}>
			{pending ? "Connexion..." : "Login"}
		</Button>
	);
}

export default function LoginForm() {
	const [state, formAction] = useActionState(loginAction, initialState);
	const [username, setUsername] = useState("");

	return (
		<form action={formAction} className="w-full max-w-sm">
			<FieldGroup>
				<Field>
					<FieldLabel htmlFor="username">Username</FieldLabel>
					<Input
						id="username"
						name="username"
						value={username}
						onChange={(event) => setUsername(event.target.value)}
					/>
				</Field>

				<Field>
					<FieldLabel htmlFor="password">Password</FieldLabel>
					<Input id="password" name="password" type="password" />
				</Field>

				{state.error && <FieldError>{state.error}</FieldError>}

				<Field>
					<SubmitButton />
				</Field>

				<Field>
					<Button asChild variant="outline">
						<a href={`${process.env.NEXT_PUBLIC_API_URL}/oauth/google/redirect`}>
							Se connecter avec Google
						</a>
					</Button>
				</Field>

				<p className="text-sm text-center">
					<a href="/forgot-password" className="font-semibold text-primary hover:underline">Mot de passe oublié ?</a>
				</p>

				<p className="text-sm text-center">
					Pas de compte ? <a href="/register" className="font-semibold text-primary hover:underline">Inscris-toi</a>
				</p>
			</FieldGroup>
		</form>
	);
}
