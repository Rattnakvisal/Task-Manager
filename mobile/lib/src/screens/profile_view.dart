import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../app.dart';
import '../services/api_exception.dart';
import '../state/app_state.dart';
import 'onboarding_screen.dart';

/// Account controls backed by the current session and its task data.
class ProfileView extends StatefulWidget {
  const ProfileView({
    super.key,
    required this.state,
    required this.onOpenFocus,
  });
  final AppState state;
  final VoidCallback onOpenFocus;

  @override
  State<ProfileView> createState() => _ProfileViewState();
}

class _ProfileViewState extends State<ProfileView> {
  bool _signingOut = false;
  bool _confirmingSignOut = false;
  AppState get state => widget.state;

  String get _syncLabel => state.lastSyncedAt == null
      ? 'Not refreshed in this session'
      : 'Updated ${DateFormat('h:mm a').format(state.lastSyncedAt!)}';

  Future<void> _sheet(String title, Widget child) => showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    showDragHandle: true,
    builder: (context) => SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(
        24,
        0,
        24,
        24 + MediaQuery.viewInsetsOf(context).bottom,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 16),
          child,
        ],
      ),
    ),
  );

  Future<void> _editProfile() async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (_) => _AccountEditor(state: state),
    );
    if (saved == true && mounted) {
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Profile updated')));
    }
  }

  Future<void> _showSync() =>
      _sheet('Cloud Sync & Linked Storage', _SyncPanel(state: state));

  Future<void> _signOut() async {
    if (_signingOut || _confirmingSignOut) return;
    _confirmingSignOut = true;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sign out of this device?'),
        content: const Text(
          'Your tasks stay in your account. You will need to sign in again on this device.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Sign Out'),
          ),
        ],
      ),
    );
    _confirmingSignOut = false;
    if (confirmed != true || !mounted || _signingOut) return;
    setState(() => _signingOut = true);
    try {
      await state.logout();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              error is ApiException
                  ? error.message
                  : 'Could not sign out. Please try again.',
            ),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _signingOut = false);
    }
  }

  Widget _section(String title, List<Widget> children) => Padding(
    padding: const EdgeInsets.only(top: 24),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 10),
          child: Text(
            title,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: Color(0xFF64748B),
            ),
          ),
        ),
        Card(
          margin: EdgeInsets.zero,
          clipBehavior: Clip.antiAlias,
          child: Column(
            children: [
              for (var i = 0; i < children.length; i++) ...[
                if (i > 0) const Divider(height: 1, indent: 64),
                children[i],
              ],
            ],
          ),
        ),
      ],
    ),
  );

  Widget _tile(
    IconData icon,
    String title,
    String subtitle,
    VoidCallback onTap,
  ) => ListTile(
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
    leading: Icon(icon, color: brandPurple),
    title: Text(
      title,
      style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
    ),
    subtitle: Text(
      subtitle,
      style: const TextStyle(fontSize: 12.5, height: 1.4),
    ),
    trailing: const Icon(Icons.chevron_right_rounded, size: 20),
    onTap: onTap,
  );

  @override
  Widget build(BuildContext context) {
    final user = state.user;
    if (user == null) return const SizedBox.shrink();
    final completed = state.tasks.where((task) => task.completed).length;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          'SETTINGS & ACCOUNT',
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w700,
            letterSpacing: 1,
            color: brandPurple,
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Profile',
          style: TextStyle(
            fontSize: 28,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.7,
          ),
        ),
        const SizedBox(height: 4),
        const Text(
          'Your account. Your progress. Your workspace.',
          style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
        ),
        const SizedBox(height: 24),
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFEFEDFF), Color(0xFFF9F8FF)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: const Color(0xFFE0DAFF)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: brandPurple,
                foregroundColor: Colors.white,
                child: Text(
                  user.name.trim().isEmpty
                      ? 'U'
                      : user.name.trim().characters.first.toUpperCase(),
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Text(
                user.name,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                user.email,
                style: const TextStyle(color: Color(0xFF64748B)),
              ),
              const SizedBox(height: 16),
              FilledButton.tonalIcon(
                onPressed: _editProfile,
                icon: const Icon(Icons.edit_outlined, size: 18),
                label: const Text('Edit profile'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        LayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.maxWidth < 350
                ? (constraints.maxWidth - 12) / 2
                : (constraints.maxWidth - 24) / 3;
            return Wrap(
              spacing: 12,
              runSpacing: 12,
              children: [
                _stat(width, '$completed', 'Completed', Icons.task_alt_rounded),
                _stat(
                  width,
                  '${state.tasks.length - completed}',
                  'Active tasks',
                  Icons.checklist_rounded,
                ),
                _stat(
                  width,
                  '${state.focus.timer.completedMinutes}m',
                  'Focus today',
                  Icons.timer_outlined,
                ),
              ],
            );
          },
        ),
        _section('WORKSPACE', [
          _tile(
            Icons.cloud_sync_outlined,
            'Cloud Sync & Linked Storage',
            _syncLabel,
            _showSync,
          ),
          _tile(
            Icons.center_focus_strong_rounded,
            'Focus preferences',
            '${state.focus.timer.workMinutes}-minute sessions · ${state.focus.timer.dailyGoalSessions} daily goal',
            widget.onOpenFocus,
          ),
        ]),
        _section('ACCOUNT & HELP', [
          _tile(
            Icons.person_outline_rounded,
            'Account details',
            'Update your name and view your sign-in email',
            _editProfile,
          ),
          _tile(
            Icons.shield_outlined,
            'Data & session',
            'How this device connects to your account',
            () => _sheet(
              'Data & session',
              const Text(
                'Your tasks are stored in your account on the server. The app stores a sign-in token in secure device storage.\n\nFocus sessions and streak history are saved on this device for your account.\n\nSigning out revokes this device’s current token. It does not delete your tasks.',
                style: TextStyle(height: 1.6),
              ),
            ),
          ),
          _tile(
            Icons.help_outline_rounded,
            'App tour',
            'Revisit the task and planning walkthrough',
            () {
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (tourContext) => OnboardingScreen(
                    onGetStarted: () => Navigator.pop(tourContext),
                    onSignIn: () => Navigator.pop(tourContext),
                  ),
                ),
              );
            },
          ),
        ]),
        const SizedBox(height: 24),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            foregroundColor: const Color(0xFFBA1A1A),
            padding: const EdgeInsets.all(16),
          ),
          onPressed: _signingOut ? null : _signOut,
          icon: _signingOut
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.logout_rounded, size: 20),
          label: Text(_signingOut ? 'Signing out…' : 'Sign Out'),
        ),
        const SizedBox(height: 16),
        const Center(
          child: Text(
            'WorkMind · Personal workspace',
            style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
          ),
        ),
      ],
    );
  }

  Widget _stat(
    double width,
    String value,
    String label,
    IconData icon,
  ) => SizedBox(
    width: width,
    child: Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 20, color: brandPurple),
            const SizedBox(height: 10),
            Text(
              value,
              style: const TextStyle(fontSize: 23, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
          ],
        ),
      ),
    ),
  );
}

class _SyncPanel extends StatefulWidget {
  const _SyncPanel({required this.state});
  final AppState state;
  @override
  State<_SyncPanel> createState() => _SyncPanelState();
}

class _SyncPanelState extends State<_SyncPanel> {
  bool _busy = false;
  String? _error;
  bool _succeeded = false;

  Future<void> _sync() async {
    if (_busy) return;
    setState(() {
      _busy = true;
      _error = null;
      _succeeded = false;
    });
    final userId = widget.state.user?.id;
    try {
      await widget.state.refresh();
      if (!mounted) return;
      if (userId == null || widget.state.user?.id != userId) {
        Navigator.pop(context);
        return;
      }
      setState(() => _succeeded = true);
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is ApiException
              ? error.message
              : 'Could not sync. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    mainAxisSize: MainAxisSize.min,
    children: [
      const Text(
        'Refresh your tasks from your account. Focus history is stored on this device.',
        style: TextStyle(height: 1.5),
      ),
      const SizedBox(height: 16),
      Text('${widget.state.tasks.length} tasks in your workspace'),
      const SizedBox(height: 16),
      if (_error != null) ...[
        Semantics(
          liveRegion: true,
          child: Text(
            _error!,
            style: const TextStyle(color: Color(0xFFBA1A1A)),
          ),
        ),
        const SizedBox(height: 12),
      ],
      if (_succeeded) ...[
        Semantics(
          liveRegion: true,
          child: const Text(
            'Synchronized!',
            style: TextStyle(color: Color(0xFF137333)),
          ),
        ),
        const SizedBox(height: 12),
      ],
      FilledButton.icon(
        onPressed: _busy ? null : _sync,
        icon: _busy
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : const Icon(Icons.sync_rounded),
        label: Text(
          _busy
              ? 'Syncing…'
              : _error != null
              ? 'Retry sync'
              : 'Sync Workspace Now',
        ),
      ),
      TextButton(
        onPressed: () => Navigator.pop(context),
        child: const Text('Close'),
      ),
    ],
  );
}

class _AccountEditor extends StatefulWidget {
  const _AccountEditor({required this.state});
  final AppState state;
  @override
  State<_AccountEditor> createState() => _AccountEditorState();
}

class _AccountEditorState extends State<_AccountEditor> {
  final _form = GlobalKey<FormState>();
  late final _name = TextEditingController(text: widget.state.user?.name ?? '');
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_busy || !_form.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.state.updateProfileName(_name.text);
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is ApiException
              ? error.message
              : 'Could not save your profile. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_busy,
    child: SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(
        24,
        0,
        24,
        24 + MediaQuery.viewInsetsOf(context).bottom,
      ),
      child: Form(
        key: _form,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Edit profile', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            const Text('Your name is shared with your web account.'),
            const SizedBox(height: 20),
            TextFormField(
              controller: _name,
              enabled: !_busy,
              maxLength: 100,
              textCapitalization: TextCapitalization.words,
              textInputAction: TextInputAction.done,
              autofillHints: const [AutofillHints.name],
              decoration: const InputDecoration(
                labelText: 'Full name',
                border: OutlineInputBorder(),
              ),
              validator: (value) => value == null || value.trim().isEmpty
                  ? 'Please enter your name.'
                  : null,
              onFieldSubmitted: (_) => _save(),
            ),
            const SizedBox(height: 12),
            const Text(
              'Sign-in email',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 4),
            SelectableText(widget.state.user?.email ?? ''),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Semantics(
                liveRegion: true,
                child: Text(
                  _error!,
                  style: const TextStyle(color: Color(0xFFBA1A1A)),
                ),
              ),
              const SizedBox(height: 12),
            ],
            FilledButton(
              onPressed: _busy ? null : _save,
              child: Text(_busy ? 'Saving…' : 'Save changes'),
            ),
            TextButton(
              onPressed: _busy ? null : () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
          ],
        ),
      ),
    ),
  );
}
