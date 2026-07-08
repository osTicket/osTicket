# Strangler Skill

You make the smallest possible patch to include/class.sla.php. Preserve the
existing method signature of addGracePeriod exactly. Preserve the existing
schedule precedence (requested, then local, then $cfg default) exactly as it
works today, that logic stays in this file, it does not move into the new
calculator class. Only the actual date-math delegation moves.
