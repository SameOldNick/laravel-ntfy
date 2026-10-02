<?php

namespace SameOldNick\Ntfy\Enums;

enum AuthMethod: string
{
    case Login = 'login';
    case Token = 'token';
    case None = 'none';
}
