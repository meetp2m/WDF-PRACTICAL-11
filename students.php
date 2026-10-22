
<?php
$conn = new mysqli("localhost", "root", "", "studentdb");

if ($conn->connect_error) {
    die("Database connection failed");
}

$msg = "";

if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = $_POST['course'];

    $stmt = $conn->prepare("INSERT INTO students (name, email, course) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $course);

    if ($stmt->execute()) {
        $msg = "Student added successfully";
    } else {
        $msg = "Student not added";
    }
}

if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = $_POST['course'];

    $stmt = $conn->prepare("UPDATE students SET name=?, email=?, course=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $course, $id);

    if ($stmt->execute()) {
        $msg = "Student updated successfully";
    } else {
        $msg = "Update failed";
    }
}

if (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $msg = "Student deleted successfully";
    } else {
        $msg = "Student not found";
    }
}

$edit = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

$search = trim($_GET['search'] ?? "");
$courseFilter = $_GET['course'] ?? "";

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if ($search != "") {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR course LIKE ?)";
    $word = "%$search%";
    $params[] = $word;
    $params[] = $word;
    $params[] = $word;
    $types .= "sss";
}

if ($courseFilter != "") {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
</head>
<body>

<h2>Student Management System</h2>

<p><?php echo e($msg); ?></p>

<h3><?php echo $edit ? "Edit Student" : "Add Student"; ?></h3>

<form method="post">
    <input type="hidden" name="id"
           value="<?php echo e($edit['id'] ?? ''); ?>">

    Name:
    <input type="text" name="name" required
           value="<?php echo e($edit['name'] ?? ''); ?>">

    Email:
    <input type="email" name="email" required
           value="<?php echo e($edit['email'] ?? ''); ?>">

    Course:
    <select name="course" required>
        <option value="">Select Course</option>
        <option value="BCA"
        <?php if (($edit['course'] ?? '') == 'BCA') echo 'selected'; ?>>
        BCA</option>

        <option value="BBA"
        <?php if (($edit['course'] ?? '') == 'BBA') echo 'selected'; ?>>
        BBA</option>
    </select>

    <?php if ($edit) { ?>
        <button type="submit" name="update">Update</button>
        <a href="students.php">Cancel</a>
    <?php } else { ?>
        <button type="submit" name="add">Add Student</button>
    <?php } ?>
</form>

<hr>

<h3>Search and Filter</h3>

<form method="get">
    <input type="text" name="search"
           placeholder="Search name, email or course"
           value="<?php echo e($search); ?>">

    <select name="course">
        <option value="">All Courses</option>
        <option value="BCA"
        <?php if ($courseFilter == 'BCA') echo 'selected'; ?>>
        BCA</option>

        <option value="BBA"
        <?php if ($courseFilter == 'BBA') echo 'selected'; ?>>
        BBA</option>
    </select>

    <button type="submit">Search</button>
    <a href="students.php">Reset</a>
</form>

<h3>Student List</h3>

<table border="1" cellpadding="8">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Course</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?php echo e($row['id']); ?></td>
    <td><?php echo e($row['name']); ?></td>
    <td><?php echo e($row['email']); ?></td>
    <td><?php echo e($row['course']); ?></td>
    <td>
        <a href="students.php?edit=<?php echo e($row['id']); ?>">
            Edit
        </a>

        <form method="post" style="display:inline"
              onsubmit="return confirm('Delete this student?');">
            <input type="hidden" name="id"
                   value="<?php echo e($row['id']); ?>">
            <button type="submit" name="delete">Delete</button>
        </form>
    </td>
</tr>
<?php } ?>

</table>

</body>
</html>
