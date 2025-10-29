# 🔍 Template Debugging - Next Steps

## What I've Done

I've added **HIGHLY VISIBLE** debug markers to definitively identify what's happening:

### 1. Rainbow Gradient Banner
At the very top of the course page, there's now a **huge rainbow gradient banner** (red-green-blue gradient with yellow border) that says:
```
🔧 DEBUG: INTEB TEMPLATE IS ACTIVE - edw_course_header1.mustache 🔧
```

**If you see this banner** → Our INTEB template is loading correctly ✅
**If you DON'T see this banner** → A different template is being used ❌

### 2. Green Debug Box
Where the teachers should appear, there's now a **green-on-black terminal-style debug box** that shows:
- ✓ teachers context exists
- ✓ hasteachers = TRUE
- ✓ Instructor: [Name] (ID: [ID])

This shows exactly what data is being passed to the template.

### 3. Actual Teachers Display
Below the debug box, the teachers should display normally with avatars and names.

---

## 🚀 What You Need to Do Now

### Step 1: Run the Verification Script

Open this URL in your browser (replace `yoursite` with your actual Moodle URL):

```
http://yoursite/theme/inteb/verify_template.php?courseid=206
```

**What it does:**
- ✓ Checks that the INTEB template file exists and has the debug markers
- ✓ Shows the `courseheaderdesign` configuration value
- ✓ **PURGES ALL CACHES** (Moodle, theme, and physically deletes compiled Mustache templates)
- ✓ Tests the coursehandler to show teacher data
- ✓ Provides direct link to the course page

**Important:** You need admin/site config permissions to run this script.

### Step 2: Clear Your Browser Cache

After running the verification script, you **MUST** clear your browser cache:

#### Option A: Hard Refresh (Easiest)
- **Windows/Linux:** Press `Ctrl + Shift + R`
- **Mac:** Press `Cmd + Shift + R`

#### Option B: Clear All Cache
- **Chrome/Edge:** Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
- **Firefox:** Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
- Select "Cached images and files" and clear

#### Option C: Use Incognito/Private Window
- Open a new incognito/private browsing window
- Navigate to your course page
- This bypasses all browser cache

### Step 3: Visit the Course Page

Go to your course:
```
http://yoursite/course/view.php?id=206
```

### Step 4: Look for the Debug Markers

**You should see TWO visible debug sections:**

1. **🌈 RAINBOW BANNER at the top** (huge, can't be missed)
   - If you see it: ✅ INTEB template is active
   - If you don't see it: ❌ Different template is being used

2. **🟢 GREEN DEBUG BOX** in the course header area
   - Shows what teacher data is available
   - Lists each instructor by name and ID

3. **Teachers display** (below the debug box)
   - Should show teacher avatar and name

---

## 📊 Report Back

Please tell me what you see:

### Question 1: Do you see the rainbow banner?
- [ ] YES - I see the rainbow banner "DEBUG: INTEB TEMPLATE IS ACTIVE"
- [ ] NO - I don't see any rainbow banner

### Question 2: Do you see the green debug box?
- [ ] YES - I see it, and it says: `[paste what it says here]`
- [ ] NO - I don't see any green debug box

### Question 3: What about the teachers?
- [ ] I see the teacher avatar and name displayed correctly
- [ ] I see the debug box but not the teacher display
- [ ] I don't see anything about teachers

### Question 4: Can you share a screenshot?
Please take a screenshot of the entire course page header area so I can see what's rendering.

---

## 🔧 Troubleshooting

### If you DON'T see the rainbow banner:
This means the INTEB template is NOT being loaded. Possible causes:
1. **Browser cache** - Try incognito/private window
2. **Different page** - Make sure you're on a course page, not dashboard
3. **Focus mode** - Check if focus mode or another feature changes the layout
4. **Wrong course header design** - Check if courseheaderdesign is set to "1"

### If you see the rainbow banner but NO green debug box:
This means the template is loading, but the teachers section is not rendering. Check:
1. The verification script output - does it show teachers data?
2. Are you logged in as a user who can see teachers?

### If you see both debug sections correctly:
Perfect! The template is working. We just need to remove the debug code and finalize.

---

## 📁 Files Modified

All changes are on branch: `claude/inteb-show-both-teacher-roles-011CUbuRXKwqmkNp9N5HmUy4`

### Core Changes:
1. **theme/inteb/classes/coursehandler.php** - Gets both teacher roles
2. **theme/inteb/classes/output/core_renderer.php** - Uses inteb coursehandler
3. **theme/inteb/templates/theme_remui/edw_course_header1.mustache** - Template with debug markers

### Debug Tools:
1. **theme/inteb/debug_teachers.php** - CLI/web tool to check teacher roles
2. **theme/inteb/force_template_rebuild.php** - Aggressive cache purge script
3. **theme/inteb/verify_template.php** - Comprehensive verification tool (NEW!)

---

## 🎯 Expected Outcome

After following all steps, you should see:
1. ✅ Rainbow banner confirming INTEB template is active
2. ✅ Green debug box showing teacher data
3. ✅ Teacher avatar and name displayed in the course header

Once we confirm the template is loading and showing teachers correctly, I'll remove all the debug code and create the final pull request.

---

**Let me know what you see after running these steps! 🚀**
